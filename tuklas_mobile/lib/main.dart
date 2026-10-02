import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart'; // Import the webview package

void main() {
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return const MaterialApp(
      title: 'Tuklas Mobile App',
      debugShowCheckedModeBanner: false, // Removes the red debug banner
      home: MyWebViewPage(),
    );
  }
}

class MyWebViewPage extends StatefulWidget {
  const MyWebViewPage({super.key});

  @override
  State<MyWebViewPage> createState() => _MyWebViewPageState();
}

class _MyWebViewPageState extends State<MyWebViewPage> {
  // 1. Define your Laravel URL string here. 
  // We use 10.0.2.2 so the Android Studio Emulator can see your computer's localhost.
  final String _laravelUrl = "http://10.0.2.2:8000"; 
  
  late final WebViewController _controller;

  @override
  void initState() {
    super.initState();
    
    // 2. Initialize the WebViewController and load the Laravel URL
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted) // Essential for modern websites/Laravel
      ..loadRequest(Uri.parse(_laravelUrl));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      // SafeArea prevents your website from getting cut off by the phone's notch or camera hole
      body: SafeArea( 
        child: WebViewWidget(controller: _controller),
      ),
    );
  }
}