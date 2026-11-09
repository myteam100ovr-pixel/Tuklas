package com.tuklas_mobile

import io.flutter.embedding.android.FlutterActivity
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.plugin.common.MethodChannel

class MainActivity : FlutterActivity() {
    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)
        MethodChannel(flutterEngine.dartExecutor.binaryMessenger, "com.tuklas_mobile/app_version")
            .setMethodCallHandler { call, result ->
                if (call.method == "versionCode") {
                    val info = packageManager.getPackageInfo(packageName, 0)
                    result.success(info.longVersionCode.toInt())
                } else {
                    result.notImplemented()
                }
            }
    }
}
