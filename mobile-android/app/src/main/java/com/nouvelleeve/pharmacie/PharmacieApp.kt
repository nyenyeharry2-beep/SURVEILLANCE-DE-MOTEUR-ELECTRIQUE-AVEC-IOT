package com.nouvelleeve.pharmacie

import android.app.Application
import android.webkit.CookieManager

class PharmacieApp : Application() {
    override fun onCreate() {
        super.onCreate()
        instance = this
        CookieManager.getInstance().setAcceptCookie(true)
    }

    companion object {
        lateinit var instance: PharmacieApp
            private set
    }
}
