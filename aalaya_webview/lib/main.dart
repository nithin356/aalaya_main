import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';
import 'package:url_launcher/url_launcher.dart';
import 'dart:async';
import 'dart:io' show Platform;

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'aalaya',
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: Colors.deepPurple),
        useMaterial3: true,
      ),
      home: const WebViewScreen(),
    );
  }
}

class WebViewScreen extends StatefulWidget {
  const WebViewScreen({super.key});

  @override
  State<WebViewScreen> createState() => _WebViewScreenState();
}

class _WebViewScreenState extends State<WebViewScreen> {
  late final WebViewController _controller;
  late String _initialUrl;
  late StreamSubscription<dynamic> _deepLinkSubscription;

  @override
  void initState() {
    super.initState();
    _initialUrl = 'https://aalaya.info';
    _initializeWebView();
    _handleDeepLinks();
  }

  Future<void> _handleDeepLinks() async {
    if (Platform.isAndroid) {
      // For Android, we need to listen to intent extras
      // The URL will be passed via the initial route
      try {
        // Get the initial deep link from the platform channel
        _handleIncomingLink();
      } catch (e) {
        debugPrint('Error setting up deep link listener: $e');
      }
    }
  }

  Future<void> _handleIncomingLink() async {
    // On Android, deep links come through the app's launch intent
    // This will be triggered when app is opened via deep link
  }

  void _loadDeepLinkUrl(String url) {
    if (mounted) {
      final uri = Uri.parse(url);
      if ((uri.host == 'aalaya.info' || uri.host == 'www.aalaya.info') &&
          (uri.scheme == 'https' || uri.scheme == 'http')) {
        setState(() {
          _initialUrl = url;
        });
        _controller.loadRequest(Uri.parse(url));
      }
    }
  }

  void _initializeWebView() {
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(const Color(0x00000000))
      ..setNavigationDelegate(
        NavigationDelegate(
          onNavigationRequest: (NavigationRequest request) async {
            final Uri uri = Uri.parse(request.url);
            final String url = request.url.toLowerCase();

            // Broad check for Downloads (Files, Blobs, Query Params)
            if (url.endsWith('.pdf') ||
                url.endsWith('.vcf') ||
                url.endsWith('.jpg') ||
                url.endsWith('.jpeg') ||
                url.endsWith('.png') ||
                url.endsWith('.apk') ||
                url.contains('download') ||
                url.contains('qr=') ||  // Handle QR code generation links
                uri.scheme == 'blob') {
               if (await canLaunchUrl(uri)) {
                await launchUrl(uri, mode: LaunchMode.externalApplication);
              }
              return NavigationDecision.prevent;
            }

            if (uri.scheme == 'http' || uri.scheme == 'https') {
              return NavigationDecision.navigate;
            } else {
              if (await canLaunchUrl(uri)) {
                await launchUrl(uri, mode: LaunchMode.externalApplication);
              }
              return NavigationDecision.prevent;
            }
          },
        ),
      )
      ..loadRequest(Uri.parse(_initialUrl));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: WebViewWidget(controller: _controller),
      ),
    );
  }

  @override
  void dispose() {
    _deepLinkSubscription.cancel();
    super.dispose();
  }
}
