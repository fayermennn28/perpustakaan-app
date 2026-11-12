class User {
  final int id;
  final String name;
  final String email;
  final String? memberCode;
  final String? phone;
  final String? address;
  final String? photoUrl;

  const User({
    required this.id,
    required this.name,
    required this.email,
    this.memberCode,
    this.phone,
    this.address,
    this.photoUrl,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'] as int,
      name: json['name'] as String,
      email: json['email'] as String,
      memberCode: json['member_code'] as String?,
      phone: json['phone'] as String?,
      address: json['address'] as String?,
      photoUrl: json['photo_url'] as String?,
    );
  }
}
