<?php
/**
 * Migration: Full Recalculation of Share Commissions
 * 
 * This script performs a complete reset and replay:
 * 1. Deletes all existing share_commission entries from referral_transactions
 * 2. Clears all share_transactions
 * 3. Recalculates base points per user (subscription_reward + manual adjustments)
 * 4. Resets total_points (to base) and total_shares (to 0) for all users
 * 5. Recalculates total_investment_amount from the investments table
 * 6. Replays every investment chronologically:
 *    - Investment amount → added to user's points
 *    - Points >= share_threshold → earn shares (remainder carries over)
 *    - For each share earned → flat commission to L1 & L2 referrers
 *    - Commission points also go through threshold (can earn shares for referrer)
 * 
 * IMPORTANT: Run this only once. Back up the database before running.
 */

require_once 'includes/db.php';
$pdo = getDB();

// ─── Configuration ───────────────────────────────────────────
$DRY_RUN = isset($_GET['dry_run']) && $_GET['dry_run'] === '1'; // ?dry_run=1 to preview without changes

echo "<html><head><title>Recalculate Share Commissions</title>";
echo "<style>body{font-family:monospace;padding:20px;max-width:1000px;margin:auto}
table{border-collapse:collapse;width:100%;margin:10px 0}
th,td{border:1px solid #ccc;padding:6px 10px;text-align:left;font-size:13px}
th{background:#f5f5f5}
.ok{color:green}.err{color:red}.warn{color:orange}.info{color:#555}
h2{margin-top:30px;border-bottom:2px solid #333;padding-bottom:5px}
</style></head><body>";

echo "<h1>🔄 Share Commission Recalculation" . ($DRY_RUN ? " <span class='warn'>[DRY RUN]</span>" : "") . "</h1>";

try {
    // ─── Step 0: Read system config ──────────────────────────
    echo "<h2>Step 0: Load Configuration</h2>";
    
    $configStmt = $pdo->query("SELECT config_key, config_value FROM system_config");
    $config = [];
    foreach ($configStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $config[$row['config_key']] = $row['config_value'];
    }
    
    $shareThreshold = floatval($config['share_threshold'] ?? 11111);
    $level1CommFlat = floatval($config['referral_level1_commission_flat'] ?? 2000);
    $level2CommFlat = floatval($config['referral_level2_commission_flat'] ?? 1000);
    
    echo "<p>Share Threshold: <b>" . number_format($shareThreshold, 2) . "</b></p>";
    echo "<p>L1 Commission (flat per share): <b>" . number_format($level1CommFlat, 2) . "</b></p>";
    echo "<p>L2 Commission (flat per share): <b>" . number_format($level2CommFlat, 2) . "</b></p>";
    
    if ($shareThreshold <= 0) {
        echo "<p class='err'>❌ share_threshold is 0 or negative. Aborting.</p>";
        exit;
    }

    // ─── Step 1: Snapshot current state (for audit/comparison) ──
    echo "<h2>Step 1: Current State Snapshot</h2>";
    
    $snapshotStmt = $pdo->query("
        SELECT id, full_name, total_points, total_shares, total_investment_amount, referred_by
        FROM users WHERE is_deleted = 0
        ORDER BY id
    ");
    $snapshot = $snapshotStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count existing share commissions and investment rewards
    $commCountStmt = $pdo->query("SELECT COUNT(*) FROM referral_transactions WHERE transaction_type IN ('share_commission', 'investment_reward')");
    $existingCommCount = $commCountStmt->fetchColumn();
    echo "<p>Found <b>{$existingCommCount}</b> existing share_commission/investment_reward records to delete.</p>";
    
    $shareTransCount = $pdo->query("SELECT COUNT(*) FROM share_transactions")->fetchColumn();
    echo "<p>Found <b>{$shareTransCount}</b> existing share_transactions to delete.</p>";
    echo "<p>Found <b>" . count($snapshot) . "</b> active users.</p>";

    // ─── Step 2: Calculate base points per user ──────────────
    echo "<h2>Step 2: Calculate Base Points (Non-Investment, Non-Commission)</h2>";
    
    // Base points = subscription_reward + manual adjustments ONLY
    // Exclude share_commission (will be recalculated) and investment_reward (legacy, covered by replay)
    $basePointsStmt = $pdo->query("
        SELECT user_id, SUM(points_earned) as base_points
        FROM referral_transactions
        WHERE transaction_type NOT IN ('share_commission', 'investment_reward')
        GROUP BY user_id
    ");
    $basePointsMap = [];
    foreach ($basePointsStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $basePointsMap[intval($row['user_id'])] = floatval($row['base_points']);
    }
    
    $usersWithBasePoints = count($basePointsMap);
    echo "<p>Calculated base points for <b>{$usersWithBasePoints}</b> users.</p>";

    // ─── Step 3: Load referral chain ─────────────────────────
    echo "<h2>Step 3: Load Referral Chain</h2>";
    
    $refStmt = $pdo->query("SELECT id, referred_by FROM users WHERE is_deleted = 0");
    $referralMap = []; // user_id => referred_by
    foreach ($refStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $referralMap[intval($row['id'])] = $row['referred_by'] ? intval($row['referred_by']) : null;
    }
    echo "<p>Loaded referral chain for <b>" . count($referralMap) . "</b> users.</p>";

    // ─── Step 4: Load all investments chronologically ────────
    echo "<h2>Step 4: Load Investments</h2>";
    
    $invStmt = $pdo->query("SELECT id, user_id, amount, created_at FROM investments ORDER BY created_at ASC, id ASC");
    $investments = $invStmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Found <b>" . count($investments) . "</b> investment records to replay.</p>";
    
    // Calculate total investment per user (for total_investment_amount reset)
    $investmentTotals = [];
    foreach ($investments as $inv) {
        $uid = intval($inv['user_id']);
        $investmentTotals[$uid] = ($investmentTotals[$uid] ?? 0) + floatval($inv['amount']);
    }

    // ─── Step 5: Begin transaction and apply changes ─────────
    echo "<h2>Step 5: Apply Changes</h2>";
    
    if ($DRY_RUN) {
        echo "<p class='warn'>⚠️ DRY RUN — no database changes will be made.</p>";
    }
    
    if (!$DRY_RUN) {
        $pdo->beginTransaction();
    }
    
    try {
        // 5a. Delete existing share_commission and investment_reward records
        if (!$DRY_RUN) {
            $deleted = $pdo->exec("DELETE FROM referral_transactions WHERE transaction_type IN ('share_commission', 'investment_reward')");
            echo "<p class='ok'>✅ Deleted <b>{$deleted}</b> share_commission/investment_reward records.</p>";
        } else {
            echo "<p class='info'>Would delete share_commission and investment_reward records.</p>";
        }
        
        // 5b. Delete all share_transactions
        if (!$DRY_RUN) {
            $deletedShares = $pdo->exec("DELETE FROM share_transactions");
            echo "<p class='ok'>✅ Deleted <b>{$deletedShares}</b> share_transaction records.</p>";
        } else {
            echo "<p class='info'>Would delete {$shareTransCount} share_transaction records.</p>";
        }
        
        // 5c. Reset all users: total_points = base_points, total_shares = 0, total_investment_amount = recalculated
        if (!$DRY_RUN) {
            // First reset everyone to 0
            $pdo->exec("UPDATE users SET total_points = 0, total_shares = 0, total_investment_amount = 0 WHERE is_deleted = 0");
            
            // Set base points for users who have them
            $updateBaseStmt = $pdo->prepare("UPDATE users SET total_points = ? WHERE id = ?");
            foreach ($basePointsMap as $uid => $bpts) {
                $updateBaseStmt->execute([$bpts, $uid]);
            }
            
            // Set total_investment_amount from actual investments
            $updateInvStmt = $pdo->prepare("UPDATE users SET total_investment_amount = ? WHERE id = ?");
            foreach ($investmentTotals as $uid => $totalInv) {
                $updateInvStmt->execute([$totalInv, $uid]);
            }
            
            echo "<p class='ok'>✅ Reset total_points (to base), total_shares (to 0), total_investment_amount (recalculated).</p>";
        } else {
            echo "<p class='info'>Would reset all users' points/shares/investment amounts.</p>";
        }
        
        // ─── Step 6: Replay investments ──────────────────────
        echo "<h2>Step 6: Replay Investments Chronologically</h2>";
        
        // In-memory tracker for each user's current points (start from base)
        $userPoints = [];
        foreach ($referralMap as $uid => $ref) {
            $userPoints[$uid] = $basePointsMap[$uid] ?? 0;
        }
        $userShares = []; // tracks shares per user
        
        $totalSharesEarned = 0;
        $totalCommissionsAwarded = 0;
        $commissionLog = []; // for display
        
        // Prepared statements for DB writes (only used in non-dry-run)
        if (!$DRY_RUN) {
            $updateUserStmt = $pdo->prepare("UPDATE users SET total_points = ?, total_shares = total_shares + ? WHERE id = ?");
            $insertShareTxStmt = $pdo->prepare("INSERT INTO share_transactions (user_id, shares_added, reason) VALUES (?, ?, ?)");
            $insertCommStmt = $pdo->prepare("INSERT INTO referral_transactions (user_id, referred_user_id, level, points_earned, percentage, transaction_type) VALUES (?, ?, ?, ?, ?, 'share_commission')");
        }
        
        foreach ($investments as $inv) {
            $userId = intval($inv['user_id']);
            $amount = floatval($inv['amount']);
            $invId = $inv['id'];
            
            // Skip if user not in referral map (deleted user)
            if (!isset($userPoints[$userId])) {
                $userPoints[$userId] = 0;
            }
            
            // Add investment amount to user's running points
            $userPoints[$userId] += $amount;
            
            // Check for share conversion
            $investorSharesEarned = 0;
            if ($userPoints[$userId] >= $shareThreshold) {
                $investorSharesEarned = intval(floor($userPoints[$userId] / $shareThreshold));
                $userPoints[$userId] = fmod($userPoints[$userId], $shareThreshold);
                $userShares[$userId] = ($userShares[$userId] ?? 0) + $investorSharesEarned;
                $totalSharesEarned += $investorSharesEarned;
                
                if (!$DRY_RUN) {
                    $updateUserStmt->execute([$userPoints[$userId], $investorSharesEarned, $userId]);
                    $insertShareTxStmt->execute([$userId, $investorSharesEarned, "Converted from investment #{$invId} (Total points reached threshold)"]);
                }
            } else {
                if (!$DRY_RUN) {
                    // Just update points (no new shares)
                    $pdo->prepare("UPDATE users SET total_points = ? WHERE id = ?")->execute([$userPoints[$userId], $userId]);
                }
            }
            
            // If investor earned shares → distribute commissions to referrers
            if ($investorSharesEarned > 0) {
                // Level 1: direct referrer of investor
                $level1Id = $referralMap[$userId] ?? null;
                
                if ($level1Id && array_key_exists($level1Id, $referralMap)) {
                    $points1 = $investorSharesEarned * $level1CommFlat;
                    
                    if (!isset($userPoints[$level1Id])) {
                        $userPoints[$level1Id] = 0;
                    }
                    
                    $userPoints[$level1Id] += $points1;
                    
                    // Check if commission points earn shares for the referrer
                    $l1SharesEarned = 0;
                    if ($userPoints[$level1Id] >= $shareThreshold) {
                        $l1SharesEarned = intval(floor($userPoints[$level1Id] / $shareThreshold));
                        $userPoints[$level1Id] = fmod($userPoints[$level1Id], $shareThreshold);
                        $userShares[$level1Id] = ($userShares[$level1Id] ?? 0) + $l1SharesEarned;
                        $totalSharesEarned += $l1SharesEarned;
                    }
                    
                    if (!$DRY_RUN) {
                        $updateUserStmt->execute([$userPoints[$level1Id], $l1SharesEarned, $level1Id]);
                        $insertCommStmt->execute([$level1Id, $userId, 1, $points1, $level1CommFlat]);
                        
                        if ($l1SharesEarned > 0) {
                            $insertShareTxStmt->execute([$level1Id, $l1SharesEarned, "Converted from share_commission_l1 (investment #{$invId})"]);
                        }
                    }
                    
                    $totalCommissionsAwarded++;
                    $commissionLog[] = [
                        'inv_id' => $invId,
                        'investor' => $userId,
                        'shares' => $investorSharesEarned,
                        'level' => 1,
                        'referrer' => $level1Id,
                        'points' => $points1,
                        'referrer_shares' => $l1SharesEarned,
                    ];
                    
                    // Level 2: referrer of the referrer
                    $level2Id = $referralMap[$level1Id] ?? null;
                    
                    if ($level2Id && array_key_exists($level2Id, $referralMap)) {
                        $points2 = $investorSharesEarned * $level2CommFlat;
                        
                        if (!isset($userPoints[$level2Id])) {
                            $userPoints[$level2Id] = 0;
                        }
                        
                        $userPoints[$level2Id] += $points2;
                        
                        // Check if commission points earn shares for L2 referrer
                        $l2SharesEarned = 0;
                        if ($userPoints[$level2Id] >= $shareThreshold) {
                            $l2SharesEarned = intval(floor($userPoints[$level2Id] / $shareThreshold));
                            $userPoints[$level2Id] = fmod($userPoints[$level2Id], $shareThreshold);
                            $userShares[$level2Id] = ($userShares[$level2Id] ?? 0) + $l2SharesEarned;
                            $totalSharesEarned += $l2SharesEarned;
                        }
                        
                        if (!$DRY_RUN) {
                            $updateUserStmt->execute([$userPoints[$level2Id], $l2SharesEarned, $level2Id]);
                            $insertCommStmt->execute([$level2Id, $userId, 2, $points2, $level2CommFlat]);
                            
                            if ($l2SharesEarned > 0) {
                                $insertShareTxStmt->execute([$level2Id, $l2SharesEarned, "Converted from share_commission_l2 (investment #{$invId})"]);
                            }
                        }
                        
                        $totalCommissionsAwarded++;
                        $commissionLog[] = [
                            'inv_id' => $invId,
                            'investor' => $userId,
                            'shares' => $investorSharesEarned,
                            'level' => 2,
                            'referrer' => $level2Id,
                            'points' => $points2,
                            'referrer_shares' => $l2SharesEarned,
                        ];
                    }
                }
            }
        }
        
        if (!$DRY_RUN) {
            // Final pass: ensure all user points are up to date
            // (Users who had no investments but had base points are already set in step 5c)
            $pdo->commit();
        }
        
        // ─── Step 7: Summary ─────────────────────────────────
        echo "<h2>Summary</h2>";
        echo "<p class='ok'><b>Investments replayed:</b> " . count($investments) . "</p>";
        echo "<p class='ok'><b>Total shares earned (all users):</b> {$totalSharesEarned}</p>";
        echo "<p class='ok'><b>Commission records created:</b> {$totalCommissionsAwarded}</p>";
        
        // Commission detail table
        if (!empty($commissionLog)) {
            echo "<h2>Commission Details</h2>";
            echo "<table><tr><th>Inv #</th><th>Investor ID</th><th>Shares Earned</th><th>Level</th><th>Referrer ID</th><th>Points Awarded</th><th>Referrer Shares Earned</th></tr>";
            foreach ($commissionLog as $c) {
                echo "<tr><td>{$c['inv_id']}</td><td>{$c['investor']}</td><td>{$c['shares']}</td>";
                echo "<td>L{$c['level']}</td><td>{$c['referrer']}</td>";
                echo "<td>" . number_format($c['points'], 2) . "</td><td>{$c['referrer_shares']}</td></tr>";
            }
            echo "</table>";
        }
        
        // Before/After comparison
        echo "<h2>Before / After Comparison</h2>";
        echo "<table><tr><th>User ID</th><th>Name</th>";
        echo "<th>Old Points</th><th>New Points</th>";
        echo "<th>Old Shares</th><th>New Shares</th>";
        echo "<th>Old Invest Amt</th><th>New Invest Amt</th></tr>";
        
        foreach ($snapshot as $s) {
            $uid = intval($s['id']);
            $newPoints = $userPoints[$uid] ?? 0;
            $newShares = $userShares[$uid] ?? 0;
            $newInvAmt = $investmentTotals[$uid] ?? 0;
            
            $oldPoints = floatval($s['total_points']);
            $oldShares = intval($s['total_shares']);
            $oldInvAmt = floatval($s['total_investment_amount']);
            
            // Only show users with any change or any activity
            if ($oldPoints == $newPoints && $oldShares == $newShares && $oldInvAmt == $newInvAmt && $newPoints == 0 && $newShares == 0) {
                continue;
            }
            
            $ptsDiff = $newPoints != $oldPoints;
            $shrDiff = $newShares != $oldShares;
            $invDiff = $newInvAmt != $oldInvAmt;
            
            echo "<tr>";
            echo "<td>{$uid}</td><td>" . htmlspecialchars($s['full_name'] ?? 'N/A') . "</td>";
            echo "<td>" . number_format($oldPoints, 2) . "</td>";
            echo "<td style='" . ($ptsDiff ? "color:blue;font-weight:bold" : "") . "'>" . number_format($newPoints, 2) . "</td>";
            echo "<td>{$oldShares}</td>";
            echo "<td style='" . ($shrDiff ? "color:blue;font-weight:bold" : "") . "'>{$newShares}</td>";
            echo "<td>" . number_format($oldInvAmt, 2) . "</td>";
            echo "<td style='" . ($invDiff ? "color:blue;font-weight:bold" : "") . "'>" . number_format($newInvAmt, 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        if ($DRY_RUN) {
            echo "<p class='warn' style='font-size:16px;margin-top:20px;'>⚠️ This was a DRY RUN. No changes were made.</p>";
            echo "<p><a href='?dry_run=0' onclick=\"return confirm('This will permanently modify the database. Are you sure?')\" style='color:red;font-weight:bold;font-size:16px;'>🚀 Run for real (apply changes)</a></p>";
        } else {
            echo "<p class='ok' style='font-size:16px;margin-top:20px;'>✅ Migration complete! All commissions recalculated from investments.</p>";
        }
        
        echo "<p><a href='admin/dashboard.php'>← Back to Admin Dashboard</a></p>";
        
    } catch (Exception $innerEx) {
        if (!$DRY_RUN && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $innerEx;
    }
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "<p class='err' style='font-size:16px;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
?>
