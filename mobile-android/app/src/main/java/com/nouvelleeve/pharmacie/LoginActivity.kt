package com.nouvelleeve.pharmacie

import android.content.Intent
import android.os.Bundle
import android.view.View
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import com.nouvelleeve.pharmacie.databinding.ActivityLoginBinding
import kotlinx.coroutines.launch

class LoginActivity : AppCompatActivity() {

    private lateinit var binding: ActivityLoginBinding
    private lateinit var session: SessionManager

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        session = SessionManager(this)

        if (session.isLoggedIn()) {
            goMain()
            return
        }

        binding = ActivityLoginBinding.inflate(layoutInflater)
        setContentView(binding.root)
        binding.textVersion.text = "Version 1.3.1"

        lifecycleScope.launch {
            binding.textLoginError.visibility = View.GONE
            try {
                ApiClient(applicationContext).ping()
            } catch (_: Exception) {
            }
        }

        binding.btnLogin.setOnClickListener {
            binding.textLoginError.visibility = View.GONE
            val email = binding.inputEmail.text?.toString()?.trim().orEmpty()
            val password = binding.inputPassword.text?.toString().orEmpty()

            if (email.isBlank() || password.isBlank()) {
                showError(getString(R.string.login_fields_required))
                return@setOnClickListener
            }

            binding.btnLogin.isEnabled = false

            lifecycleScope.launch {
                try {
                    binding.btnLogin.text = getString(R.string.connecting)
                    val data = ApiClient(applicationContext).login(email, password)
                    session.token = data.optString("token")
                    session.userName = data.optJSONObject("user")?.optString("nom")
                    goMain()
                } catch (e: ApiException) {
                    showError(e.message ?: getString(R.string.login_error))
                } catch (_: Exception) {
                    showError(getString(R.string.login_network_error))
                } finally {
                    binding.btnLogin.isEnabled = true
                    binding.btnLogin.text = getString(R.string.btn_login)
                }
            }
        }
    }

    private fun showError(msg: String) {
        binding.textLoginError.text = msg
        binding.textLoginError.visibility = View.VISIBLE
    }

    private fun goMain() {
        startActivity(Intent(this, MainActivity::class.java))
        finish()
    }
}
