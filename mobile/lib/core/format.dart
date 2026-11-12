import 'package:intl/intl.dart';

final NumberFormat rupiahFormat = NumberFormat.currency(
  locale: 'id_ID',
  symbol: 'Rp ',
  decimalDigits: 0,
);

final DateFormat shortDate = DateFormat('d MMM', 'id_ID');
final DateFormat longDate = DateFormat('EEEE, d MMMM yyyy', 'id_ID');

String greeting() {
  final hour = DateTime.now().hour;
  if (hour < 11) return 'Selamat pagi';
  if (hour < 15) return 'Selamat siang';
  if (hour < 18) return 'Selamat sore';
  return 'Selamat malam';
}
