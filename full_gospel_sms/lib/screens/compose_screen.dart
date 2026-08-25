import 'package:flutter/material.dart';
import 'package:flutter_contacts/flutter_contacts.dart';
import 'package:permission_handler/permission_handler.dart';

import '../config/api_config.dart';
import '../services/sms_api_service.dart';
import '../theme/app_theme.dart';
import '../utils/phone_utils.dart';
import '../widgets/app_logo.dart';

class ComposeScreen extends StatefulWidget {
  const ComposeScreen({super.key});

  @override
  State<ComposeScreen> createState() => _ComposeScreenState();
}

class _ComposeScreenState extends State<ComposeScreen> {
  final _api = SmsApiService();
  final _messageController = TextEditingController();
  final _numbersController = TextEditingController();
  bool _sending = false;

  @override
  void dispose() {
    _messageController.dispose();
    _numbersController.dispose();
    super.dispose();
  }

  int get _numberCount =>
      PhoneUtils.parseNumbers(_numbersController.text).length;

  Future<void> _importContacts() async {
    final status = await Permission.contacts.request();
    if (!status.isGranted) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Autorisez l\'accès aux contacts dans les paramètres'),
          backgroundColor: Colors.orange,
        ),
      );
      return;
    }

    final contacts = await FlutterContacts.getContacts(withProperties: true);
    if (!mounted) return;

    final selected = await showDialog<List<Contact>>(
      context: context,
      builder: (context) => _ContactPickerDialog(contacts: contacts),
    );

    if (selected == null || selected.isEmpty) return;

    final numbers = <String>{};
    for (final contact in selected) {
      for (final phone in contact.phones) {
        final normalized = PhoneUtils.normalize(phone.number);
        if (normalized.length >= 9) numbers.add(normalized);
      }
    }

    if (numbers.isEmpty) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Aucun numéro valide trouvé')),
      );
      return;
    }

    final existing = PhoneUtils.parseNumbers(_numbersController.text);
    final merged = {...existing, ...numbers}.toList();
    setState(() => _numbersController.text = merged.join(', '));
  }

  Future<void> _sendSms() async {
    final message = _messageController.text.trim();
    final numbers = PhoneUtils.parseNumbers(_numbersController.text);

    if (message.isEmpty) {
      _showHelp('Écrivez votre message avant d\'envoyer');
      return;
    }
    if (numbers.isEmpty) {
      _showHelp('Ajoutez au moins un numéro de téléphone');
      return;
    }

    setState(() => _sending = true);

    try {
      final results = await _api.sendSms(
        message: message,
        mobileNumbers: numbers,
      );
      if (!mounted) return;

      await showDialog(
        context: context,
        builder: (context) => AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: const Row(
            children: [
              Icon(Icons.check_circle, color: AppTheme.brandGreen, size: 32),
              SizedBox(width: 10),
              Expanded(child: Text('Message envoyé !')),
            ],
          ),
          content: Text(
            '${results.length} SMS envoyé(s) avec succès.\n\n'
            'Expéditeur : ${ApiConfig.senderId}',
            style: const TextStyle(fontSize: 16, height: 1.5),
          ),
          actions: [
            ElevatedButton(
              onPressed: () {
                Navigator.pop(context);
                Navigator.pop(context);
              },
              child: const Text('Terminer'),
            ),
          ],
        ),
      );

      _messageController.clear();
      _numbersController.clear();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(e.toString()), backgroundColor: AppTheme.brandRed),
      );
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  void _showHelp(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message)),
    );
  }

  @override
  Widget build(BuildContext context) {
    final charCount = _messageController.text.length;

    return Scaffold(
      appBar: const AppHeader(title: 'Nouveau SMS', showLogo: false),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          _StepHeader(
            step: 1,
            title: 'Votre message',
            subtitle: 'Maximum 160 caractères par SMS',
          ),
          const SizedBox(height: 12),
          TextField(
            controller: _messageController,
            maxLines: 5,
            maxLength: 160,
            style: const TextStyle(fontSize: 16),
            onChanged: (_) => setState(() {}),
            decoration: const InputDecoration(
              hintText: 'Tapez ici votre message...',
              counterText: '',
            ),
          ),
          Align(
            alignment: Alignment.centerRight,
            child: Text(
              '$charCount / 160',
              style: TextStyle(
                fontSize: 14,
                color: charCount > 140 ? AppTheme.brandRed : AppTheme.textSecondary,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
          const SizedBox(height: 24),
          _StepHeader(
            step: 2,
            title: 'Destinataires',
            subtitle: 'Numéros séparés par virgule ou retour à la ligne',
          ),
          const SizedBox(height: 12),
          TextField(
            controller: _numbersController,
            maxLines: 4,
            keyboardType: TextInputType.phone,
            style: const TextStyle(fontSize: 16),
            onChanged: (_) => setState(() {}),
            decoration: InputDecoration(
              hintText: 'Exemple :\n243890626351\n243999999999',
              suffixText: _numberCount > 0 ? '$_numberCount num.' : null,
            ),
          ),
          const SizedBox(height: 14),
          OutlinedButton.icon(
            onPressed: _sending ? null : _importContacts,
            icon: const Icon(Icons.contacts_outlined, size: 24),
            label: const Text('Choisir dans mes contacts'),
          ),
          const SizedBox(height: 28),
          Card(
            color: AppTheme.brandGreen.withValues(alpha: 0.08),
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  const Icon(Icons.info_outline, color: AppTheme.brandGreen),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      'Le SMS sera envoyé au nom de : ${ApiConfig.senderId}',
                      style: const TextStyle(fontSize: 14, height: 1.4),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 24),
          ElevatedButton.icon(
            onPressed: _sending ? null : _sendSms,
            icon: _sending
                ? const SizedBox(
                    width: 22,
                    height: 22,
                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                  )
                : const Icon(Icons.send_rounded, size: 24),
            label: Text(_sending ? 'Envoi en cours...' : 'Envoyer maintenant'),
          ),
        ],
      ),
    );
  }
}

class _StepHeader extends StatelessWidget {
  final int step;
  final String title;
  final String subtitle;

  const _StepHeader({
    required this.step,
    required this.title,
    required this.subtitle,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        CircleAvatar(
          radius: 16,
          backgroundColor: AppTheme.brandRed,
          child: Text(
            '$step',
            style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
          ),
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: const TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: AppTheme.textPrimary,
                ),
              ),
              Text(
                subtitle,
                style: const TextStyle(fontSize: 14, color: AppTheme.textSecondary),
              ),
            ],
          ),
        ),
      ],
    );
  }
}

class _ContactPickerDialog extends StatefulWidget {
  final List<Contact> contacts;

  const _ContactPickerDialog({required this.contacts});

  @override
  State<_ContactPickerDialog> createState() => _ContactPickerDialogState();
}

class _ContactPickerDialogState extends State<_ContactPickerDialog> {
  final _selected = <String>{};
  String _filter = '';

  List<Contact> get _filtered {
    if (_filter.isEmpty) return widget.contacts;
    final lower = _filter.toLowerCase();
    return widget.contacts
        .where((c) => c.displayName.toLowerCase().contains(lower))
        .toList();
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      title: const Text('Choisir des contacts'),
      content: SizedBox(
        width: double.maxFinite,
        height: 420,
        child: Column(
          children: [
            TextField(
              decoration: const InputDecoration(
                hintText: 'Rechercher un nom...',
                prefixIcon: Icon(Icons.search),
              ),
              onChanged: (value) => setState(() => _filter = value),
            ),
            const SizedBox(height: 8),
            Expanded(
              child: _filtered.isEmpty
                  ? const Center(child: Text('Aucun contact trouvé'))
                  : ListView.builder(
                      itemCount: _filtered.length,
                      itemBuilder: (context, index) {
                        final contact = _filtered[index];
                        final id = contact.id;
                        final phones =
                            contact.phones.map((p) => p.number).join(', ');

                        return CheckboxListTile(
                          value: _selected.contains(id),
                          title: Text(
                            contact.displayName,
                            style: const TextStyle(fontSize: 16),
                          ),
                          subtitle: Text(
                            phones,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                          controlAffinity: ListTileControlAffinity.leading,
                          onChanged: (checked) {
                            setState(() {
                              if (checked == true) {
                                _selected.add(id);
                              } else {
                                _selected.remove(id);
                              }
                            });
                          },
                        );
                      },
                    ),
            ),
          ],
        ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: const Text('Annuler'),
        ),
        ElevatedButton(
          onPressed: _selected.isEmpty
              ? null
              : () {
                  final picked = widget.contacts
                      .where((c) => _selected.contains(c.id))
                      .toList();
                  Navigator.pop(context, picked);
                },
          child: Text('Ajouter (${_selected.length})'),
        ),
      ],
    );
  }
}
