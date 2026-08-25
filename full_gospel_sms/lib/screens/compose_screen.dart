import 'package:flutter/material.dart';
import 'package:flutter_contacts/flutter_contacts.dart';
import 'package:permission_handler/permission_handler.dart';

import '../config/api_config.dart';
import '../services/sms_api_service.dart';
import '../theme/app_theme.dart';
import '../utils/phone_utils.dart';

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

  Future<void> _importContacts() async {
    final status = await Permission.contacts.request();
    if (!status.isGranted) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Permission contacts refusée'),
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
        if (normalized.length >= 9) {
          numbers.add(normalized);
        }
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

    setState(() {
      _numbersController.text = merged.join(', ');
    });
  }

  Future<void> _sendSms() async {
    final message = _messageController.text.trim();
    final numbers = PhoneUtils.parseNumbers(_numbersController.text);

    if (message.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Veuillez saisir un message')),
      );
      return;
    }

    if (numbers.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Veuillez ajouter au moins un numéro')),
      );
      return;
    }

    setState(() => _sending = true);

    try {
      final results = await _api.sendSms(
        message: message,
        mobileNumbers: numbers,
      );

      if (!mounted) return;

      showDialog(
        context: context,
        builder: (context) => AlertDialog(
          title: const Row(
            children: [
              Icon(Icons.check_circle, color: AppTheme.accent),
              SizedBox(width: 8),
              Text('SMS envoyé'),
            ],
          ),
          content: Text(
            '${results.length} message(s) envoyé(s) avec succès\n'
            'Expéditeur: ${ApiConfig.senderId}',
          ),
          actions: [
            TextButton(
              onPressed: () {
                Navigator.pop(context);
                Navigator.pop(context);
              },
              child: const Text('OK'),
            ),
          ],
        ),
      );

      _messageController.clear();
      _numbersController.clear();
    } catch (e) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(e.toString()),
          backgroundColor: Colors.red,
        ),
      );
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final charCount = _messageController.text.length;

    return Scaffold(
      appBar: AppBar(title: const Text('Composer un SMS')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  const Icon(Icons.badge, color: AppTheme.primary),
                  const SizedBox(width: 12),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text(
                        'Expéditeur',
                        style: TextStyle(fontSize: 12, color: Colors.grey),
                      ),
                      Text(
                        ApiConfig.senderId,
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 16,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          TextField(
            controller: _messageController,
            maxLines: 6,
            maxLength: 160,
            onChanged: (_) => setState(() {}),
            decoration: const InputDecoration(
              labelText: 'Message',
              hintText: 'Tapez votre message ici...',
              alignLabelWithHint: true,
            ),
          ),
          Align(
            alignment: Alignment.centerRight,
            child: Text(
              '$charCount / 160 caractères',
              style: TextStyle(
                color: charCount > 160 ? Colors.red : Colors.grey,
                fontSize: 12,
              ),
            ),
          ),
          const SizedBox(height: 16),
          TextField(
            controller: _numbersController,
            maxLines: 4,
            keyboardType: TextInputType.phone,
            decoration: const InputDecoration(
              labelText: 'Numéros de téléphone',
              hintText: '243890626351, 243999999999\n(un par ligne ou séparés par virgule)',
              alignLabelWithHint: true,
            ),
          ),
          const SizedBox(height: 12),
          OutlinedButton.icon(
            onPressed: _sending ? null : _importContacts,
            icon: const Icon(Icons.contacts),
            label: const Text('Importer depuis les contacts'),
          ),
          const SizedBox(height: 24),
          ElevatedButton.icon(
            onPressed: _sending ? null : _sendSms,
            icon: _sending
                ? const SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: Colors.white,
                    ),
                  )
                : const Icon(Icons.send),
            label: Text(_sending ? 'Envoi en cours...' : 'Envoyer'),
            style: ElevatedButton.styleFrom(
              minimumSize: const Size(double.infinity, 56),
              backgroundColor: AppTheme.accent,
            ),
          ),
        ],
      ),
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
      title: const Text('Sélectionner des contacts'),
      content: SizedBox(
        width: double.maxFinite,
        height: 400,
        child: Column(
          children: [
            TextField(
              decoration: const InputDecoration(
                hintText: 'Rechercher...',
                prefixIcon: Icon(Icons.search),
              ),
              onChanged: (value) => setState(() => _filter = value),
            ),
            const SizedBox(height: 8),
            Expanded(
              child: ListView.builder(
                itemCount: _filtered.length,
                itemBuilder: (context, index) {
                  final contact = _filtered[index];
                  final id = contact.id;
                  final phones = contact.phones.map((p) => p.number).join(', ');

                  return CheckboxListTile(
                    value: _selected.contains(id),
                    title: Text(contact.displayName),
                    subtitle: Text(phones, maxLines: 1, overflow: TextOverflow.ellipsis),
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
          onPressed: () {
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
