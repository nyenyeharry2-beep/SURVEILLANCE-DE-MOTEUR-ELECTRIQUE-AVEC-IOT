package com.nouvelleeve.pharmacie

import android.content.Intent
import android.os.Bundle
import android.view.Menu
import android.view.MenuItem
import androidx.appcompat.app.AppCompatActivity
import androidx.fragment.app.Fragment
import com.nouvelleeve.pharmacie.databinding.ActivityMainBinding

class MainActivity : AppCompatActivity() {

    private lateinit var binding: ActivityMainBinding
    private lateinit var session: SessionManager

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        session = SessionManager(this)

        if (!session.isLoggedIn()) {
            startActivity(Intent(this, LoginActivity::class.java))
            finish()
            return
        }

        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)
        setSupportActionBar(binding.toolbar)
        supportActionBar?.subtitle = session.userName?.let { getString(R.string.vendor_greeting, it) }

        binding.bottomNav.setOnItemSelectedListener { item ->
            when (item.itemId) {
                R.id.nav_ventes -> showFragment(VentesFragment())
                R.id.nav_rapports -> showFragment(RapportsFragment())
                R.id.nav_alertes -> showFragment(AlertesFragment())
                else -> false
            }
        }

        if (savedInstanceState == null) {
            binding.bottomNav.selectedItemId = R.id.nav_ventes
        }
    }

    private fun showFragment(fragment: Fragment): Boolean {
        supportFragmentManager.beginTransaction()
            .replace(R.id.fragmentContainer, fragment)
            .commit()
        return true
    }

    override fun onCreateOptionsMenu(menu: Menu): Boolean {
        menuInflater.inflate(R.menu.main_menu, menu)
        return true
    }

    override fun onOptionsItemSelected(item: MenuItem): Boolean {
        if (item.itemId == R.id.action_logout) {
            lifecycleScopeLogout()
            return true
        }
        return super.onOptionsItemSelected(item)
    }

    private fun lifecycleScopeLogout() {
        Thread {
            try {
                ApiClient(session.serverUrl, session.token).logout()
            } catch (_: Exception) {
            }
            runOnUiThread {
                session.clear()
                startActivity(Intent(this, LoginActivity::class.java))
                finish()
            }
        }.start()
    }

    fun api(): ApiClient = ApiClient(session.serverUrl, session.token)
    fun sessionManager(): SessionManager = session
}
