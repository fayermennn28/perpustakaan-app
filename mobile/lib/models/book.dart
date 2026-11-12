int? _asInt(dynamic value) {
  if (value == null) return null;
  if (value is int) return value;
  return int.tryParse(value.toString());
}

class BookCategory {
  final int id;
  final String name;

  const BookCategory({required this.id, required this.name});

  factory BookCategory.fromJson(Map<String, dynamic> json) {
    return BookCategory(
      id: _asInt(json['id']) ?? 0,
      name: json['name'] as String,
    );
  }
}

class Book {
  final int id;
  final String title;
  final String author;
  final String? publisher;
  final int? year;
  final String? isbn;
  final int stock;
  final bool available;
  final String? description;
  final String? coverUrl;
  final BookCategory? category;

  const Book({
    required this.id,
    required this.title,
    required this.author,
    this.publisher,
    this.year,
    this.isbn,
    required this.stock,
    required this.available,
    this.description,
    this.coverUrl,
    this.category,
  });

  factory Book.fromJson(Map<String, dynamic> json) {
    final cat = json['category'];

    return Book(
      id: _asInt(json['id']) ?? 0,
      title: json['title'] as String,
      author: json['author'] as String,
      publisher: json['publisher'] as String?,
      year: _asInt(json['year']),
      isbn: json['isbn'] as String?,
      stock: _asInt(json['stock']) ?? 0,
      available: json['available'] as bool? ?? false,
      description: json['description'] as String?,
      coverUrl: json['cover_url'] as String?,
      category: cat is Map<String, dynamic> ? BookCategory.fromJson(cat) : null,
    );
  }
}
