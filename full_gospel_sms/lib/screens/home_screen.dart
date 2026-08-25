import 'package:flutter/material.dart';

import '../config/api_config.dart';
import '../models/sms_models.dart';
import '../services/sms_api_service.dart';
import '../theme/app_theme.dart';
import '../widgets/app_logo.dart';
import 'compose_screen.dart';
import 'history_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  final _api = SmsApiService();
  List<BalanceInfo> _balances = [];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadBalance();
  }

  Future<void> _loadBalance() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final balances = await _api.getBalance();
      if (!mounted) return;
      setState(() {
        _balances = balances;
        _loading = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  String get _creditDisplay {
    if (_balances.isEmpty) return '--';
    return _balances.first.credits;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppHeader(
        title: 'Accueil',
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh, size: 28),
            onPressed: _loading ? null : _loadBalance,
            tooltip: 'Actualiser le solde',
          ),
        ],
      ),
      body: RefreshIndicator(
        color: AppTheme.brandRed,
        onRefresh: _loadBalance,
        child: ListView(
          padding: const EdgeInsets.fromLTRB(20, 12, 20, 32),
          children: [
            Center(
              child: ClipRRect(
                borderRadius: BorderRadius.circular(16),
                child: Image.asset(
                  AppTheme.logoAsset,
                  width: 100,
                  height: 100,
                  fit: BoxFit.cover,
                ),
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              'Bienvenue',
              textAlign: TextAlign.center,
              style: TextStyle(
                fontSize: 22,
                fontWeight: FontWeight.bold,
                color: AppTheme.textPrimary,
              ),
            ),
            const SizedBox(height: 4),
            const Text(
              'Envoyez vos SMS facilement',
              textAlign: TextAlign.center,
              style: TextStyle(fontSize: 15, color: AppTheme.textSecondary),
            ),
            const SizedBox(height: 24),
            _BalanceCard(
              loading: _loading,
              error: _error,
              credit: _creditDisplay,
              onRetry: _loadBalance,
            ),
            const SizedBox(height: 16),
            Card(
              child: Padding(
                padding: const EdgeInsets.all(18),
                child: Row(
                  children: [
                    Container(
                      width: 48,
                      height: 48,
                      decoration: BoxDecoration(
                        color: AppTheme.brandGold.withValues(alpha: 0.15),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: const Icon(Icons.verified, color: AppTheme.brandGold),
                    ),
                    const SizedBox(width: 14),
                    const Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Nom affiché sur le SMS',
                            style: TextStyle(
                              fontSize: 13,
                              color: AppTheme.textSecondary,
                            ),
                          ),
                          SizedBox(height: 2),
                          Text(
                            ApiConfig.senderId,
                            style: TextStyle(
                              fontSize: 17,
                              fontWeight: FontWeight.bold,
                              color: AppTheme.brandGreen,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 28),
            const Text(
              'Que voulez-vous faire ?',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: AppTheme.textPrimary,
              ),
            ),
            const SizedBox(height: 14),
            BigActionCard(
              icon: Icons.send_rounded,
              title: 'Envoyer un SMS',
              subtitle: 'Écrire et envoyer à vos contacts',
              color: AppTheme.brandRed,
              onTap: () async {
                await Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => const ComposeScreen()),
                );
                _loadBalance();
              },
            ),
            const SizedBox(height: 12),
            BigActionCard(
              icon: Icons.history_rounded,
              title: 'Voir l\'historique',
              subtitle: 'Consulter les messages déjà envoyés',
              color: AppTheme.brandGreen,
              onTap: () {
                Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => const HistoryScreen()),
                );
              },
            ),
          ],
        ),
      ),
    );
  }
}

class _BalanceCard extends StatelessWidget {
  final bool loading;
  final String? error;
  final String credit;
  final VoidCallback onRetry;

  const _BalanceCard({
    required this.loading,
    required this.error,
    required this.credit,
    required this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    return Material(
      color: Colors.transparent,
      borderRadius: BorderRadius.circular(16),
      elevation: 2,
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.all(22),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          gradient: LinearGradient(
            colors: [
              AppTheme.brandRed.withValues(alpha: 0.95),
              AppTheme.brandRed,
            ],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.2),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(
                    Icons.account_balance_wallet_outlined,
                    color: Colors.white,
                    size: 26,
                  ),
                ),
                const SizedBox(width: 12),
                const Expanded(
                  child: Text(
                    'Crédits SMS disponibles',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 16,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 20),
            if (loading)
              const Center(
                child: CircularProgressIndicator(color: Colors.white),
              )
            else if (error != null)
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Impossible de charger le solde',
                    style: TextStyle(color: Colors.white.withValues(alpha: 0.9)),
                  ),
                  const SizedBox(height: 12),
                  ElevatedButton(
                    onPressed: onRetry,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.white,
                      foregroundColor: AppTheme.brandRed,
                      minimumSize: const Size(140, 44),
                    ),
                    child: const Text('Réessayer'),
                  ),
                ],
              )
            else
              Text(
                credit,
                style: const TextStyle(
                  color: Colors.white,
                  fontSize: 38,
                  fontWeight: FontWeight.bold,
                ),
              ),
          ],
        ),
      ),
    );
  }
}
