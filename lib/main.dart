import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

void main() {
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'CO9 HTTP Labs',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(primarySwatch: Colors.blue),
      home: const MainNavigator(),
    );
  }
}

// --- Main Navigator Menu ---
class MainNavigator extends StatelessWidget {
  const MainNavigator({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('CO9 HTTP Labs')),
      drawer: Drawer(
        child: ListView(
          padding: EdgeInsets.zero,
          children: [
            const DrawerHeader(
              decoration: BoxDecoration(color: Colors.blue),
              child: Text(
                'Select a Lab',
                style: TextStyle(color: Colors.white, fontSize: 24),
              ),
            ),
            ListTile(
              leading: const Icon(Icons.format_quote),
              title: const Text('Lab 1: Quote of the Day'),
              onTap: () => Navigator.pushReplacement(
                context,
                MaterialPageRoute(builder: (_) => const Lab1Quote()),
              ),
            ),
            ListTile(
              leading: const Icon(Icons.person_search),
              title: const Text('Lab 2: JSON Explorer'),
              onTap: () => Navigator.pushReplacement(
                context,
                MaterialPageRoute(builder: (_) => const Lab2User()),
              ),
            ),
            ListTile(
              leading: const Icon(Icons.cloud),
              title: const Text('Lab 3: Weather Peek'),
              onTap: () => Navigator.pushReplacement(
                context,
                MaterialPageRoute(builder: (_) => const Lab3Weather()),
              ),
            ),
            ListTile(
              leading: const Icon(Icons.list),
              title: const Text('Lab 4: Users List'),
              onTap: () => Navigator.pushReplacement(
                context,
                MaterialPageRoute(builder: (_) => const Lab4Users()),
              ),
            ),
            ListTile(
              leading: const Icon(Icons.search),
              title: const Text('Lab 5: Book Search'),
              onTap: () => Navigator.pushReplacement(
                context,
                MaterialPageRoute(builder: (_) => const Lab5Books()),
              ),
            ),
          ],
        ),
      ),
      body: const Center(
        child: Text('Open the drawer (top left) to select a Lab.'),
      ),
    );
  }
}

// ==========================================
// LAB 1: Quote of the Day
// ==========================================
class Lab1Quote extends StatefulWidget {
  const Lab1Quote({super.key});
  @override
  State<Lab1Quote> createState() => _Lab1QuoteState();
}

class _Lab1QuoteState extends State<Lab1Quote> {
  String quote = 'Tap the button';
  String author = '';
  bool loading = false;

  Future<void> loadQuote() async {
    setState(() => loading = true);
    try {
      final url = Uri.parse('https://api.quotable.io/random');
      final res = await http.get(url);
      final data = jsonDecode(res.body);
      setState(() {
        quote = data['content'];
        author = data['author'];
        loading = false;
      });
    } catch (e) {
      setState(() {
        quote = 'Error loading quote. Please check internet.';
        loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Lab 1: Quote of the Day')),
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              loading
                  ? const CircularProgressIndicator()
                  : Column(
                      children: [
                        Text(
                          quote,
                          style: const TextStyle(fontSize: 20),
                          textAlign: TextAlign.center,
                        ),
                        const SizedBox(height: 12),
                        if (author.isNotEmpty)
                          Text(
                            '— $author',
                            style: const TextStyle(
                              fontStyle: FontStyle.italic,
                              fontSize: 16,
                            ),
                          ),
                      ],
                    ),
              const SizedBox(height: 24),
              ElevatedButton(
                onPressed: loading ? null : loadQuote,
                child: const Text('New quote'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// ==========================================
// LAB 2: JSON Explorer
// ==========================================
class Lab2User extends StatefulWidget {
  const Lab2User({super.key});
  @override
  State<Lab2User> createState() => _Lab2UserState();
}

class _Lab2UserState extends State<Lab2User> {
  String name = '', email = '', phone = '', city = '';

  @override
  void initState() {
    super.initState();
    loadUser();
  }

  Future<void> loadUser() async {
    final res = await http.get(
      Uri.parse('https://jsonplaceholder.typicode.com/users/1'),
    );
    final data = jsonDecode(res.body);
    setState(() {
      name = data['name'];
      email = data['email'];
      phone = data['phone'];
      city = data['address']['city']; // Challenge: Nested field
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Lab 2: JSON Explorer')),
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'User Details:',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18),
            ),
            const Divider(),
            Text('Name: $name', style: const TextStyle(fontSize: 16)),
            const SizedBox(height: 8),
            Text('Email: $email', style: const TextStyle(fontSize: 16)),
            const SizedBox(height: 8),
            Text('Phone: $phone', style: const TextStyle(fontSize: 16)),
            const SizedBox(height: 8),
            Text('City: $city', style: const TextStyle(fontSize: 16)),
          ],
        ),
      ),
    );
  }
}

// ==========================================
// LAB 3: Weather Peek (City Lookup)
// ==========================================
class Lab3Weather extends StatefulWidget {
  const Lab3Weather({super.key});
  @override
  State<Lab3Weather> createState() => _Lab3WeatherState();
}

class _Lab3WeatherState extends State<Lab3Weather> {
  final cityCtrl = TextEditingController();
  String info = '';
  String temp = '';
  bool loading = false;

  Future<void> lookup() async {
    setState(() {
      loading = true;
      info = '';
      temp = '';
    });

    final city = cityCtrl.text;
    final res = await http.get(
      Uri.parse(
        'https://geocoding-api.open-meteo.com/v1/search?name=$city&count=1',
      ),
    );
    final data = jsonDecode(res.body);

    if (data['results'] != null) {
      final place = data['results'][0];
      final lat = place['latitude'];
      final lon = place['longitude'];

      // Challenge: Second call for temperature
      final weatherRes = await http.get(
        Uri.parse(
          'https://api.open-meteo.com/v1/forecast?latitude=$lat&longitude=$lon&current_weather=true',
        ),
      );
      final weatherData = jsonDecode(weatherRes.body);

      setState(() {
        info = '${place['name']}, ${place['country']}\nLat $lat, Lon $lon';
        temp = 'Temp: ${weatherData['current_weather']['temperature']}°C';
        loading = false;
      });
    } else {
      setState(() {
        info = 'City not found';
        temp = '';
        loading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Lab 3: Weather Peek')),
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          children: [
            TextField(
              controller: cityCtrl,
              decoration: const InputDecoration(
                hintText: 'Enter City (e.g. Manila)',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 12),
            ElevatedButton(
              onPressed: loading ? null : lookup,
              child: const Text('Look up'),
            ),
            const SizedBox(height: 24),
            if (loading) const CircularProgressIndicator(),
            Text(
              info,
              textAlign: TextAlign.center,
              style: const TextStyle(fontSize: 16),
            ),
            const SizedBox(height: 8),
            if (temp.isNotEmpty)
              Text(
                temp,
                style: const TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                  color: Colors.blue,
                ),
              ),
          ],
        ),
      ),
    );
  }
}

// ==========================================
// LAB 4: Users List
// ==========================================
class Lab4Users extends StatefulWidget {
  const Lab4Users({super.key});
  @override
  State<Lab4Users> createState() => _Lab4UsersState();
}

class _Lab4UsersState extends State<Lab4Users> {
  List users = [];

  @override
  void initState() {
    super.initState();
    loadUsers();
  }

  Future<void> loadUsers() async {
    final res = await http.get(
      Uri.parse('https://jsonplaceholder.typicode.com/users'),
    );
    setState(() {
      users = jsonDecode(res.body);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Lab 4: Users List')),
      body: users.isEmpty
          ? const Center(child: CircularProgressIndicator())
          : ListView.builder(
              itemCount: users.length,
              itemBuilder: (context, i) {
                return ListTile(
                  leading: const Icon(Icons.person),
                  title: Text(users[i]['name']),
                  subtitle: Text(users[i]['email']),
                  onTap: () {
                    // Challenge: Tap to open detail screen
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => Scaffold(
                          appBar: AppBar(title: Text(users[i]['name'])),
                          body: Padding(
                            padding: const EdgeInsets.all(24),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  'Name: ${users[i]['name']}',
                                  style: const TextStyle(fontSize: 16),
                                ),
                                const SizedBox(height: 8),
                                Text(
                                  'Email: ${users[i]['email']}',
                                  style: const TextStyle(fontSize: 16),
                                ),
                                const SizedBox(height: 8),
                                Text(
                                  'Phone: ${users[i]['phone']}',
                                  style: const TextStyle(fontSize: 16),
                                ),
                                const SizedBox(height: 8),
                                Text(
                                  'City: ${users[i]['address']['city']}',
                                  style: const TextStyle(fontSize: 16),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                    );
                  },
                );
              },
            ),
    );
  }
}

// ==========================================
// LAB 5: Search & Show
// ==========================================
class Lab5Books extends StatefulWidget {
  const Lab5Books({super.key});
  @override
  State<Lab5Books> createState() => _Lab5BooksState();
}

class _Lab5BooksState extends State<Lab5Books> {
  final searchCtrl = TextEditingController();
  List books = [];
  bool loading = false;

  Future<void> search() async {
    setState(() {
      loading = true;
      books = [];
    });
    final q = searchCtrl.text;
    final res = await http.get(
      Uri.parse('https://openlibrary.org/search.json?q=$q&limit=10'),
    );
    final data = jsonDecode(res.body);
    setState(() {
      books = data['docs'];
      loading = false;
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Lab 5: Search & Show')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            TextField(
              controller: searchCtrl,
              decoration: const InputDecoration(
                hintText: 'Search books (e.g. flutter)',
                border: OutlineInputBorder(),
              ),
            ),
            const SizedBox(height: 12),
            ElevatedButton(
              onPressed: loading ? null : search,
              child: const Text('Search'),
            ),
            const SizedBox(height: 16),
            loading
                ? const CircularProgressIndicator()
                : Expanded(
                    child: books.isEmpty
                        ? const Center(
                            child: Text(
                              'No results found',
                              style: TextStyle(fontSize: 16),
                            ),
                          )
                        : ListView.builder(
                            itemCount: books.length,
                            itemBuilder: (context, i) {
                              final b = books[i];
                              final year = b['first_publish_year'] != null
                                  ? '${b['first_publish_year']}'
                                  : '—';
                              final author = (b['author_name'] != null)
                                  ? b['author_name'][0]
                                  : 'Unknown';
                              return Card(
                                margin: const EdgeInsets.only(bottom: 8),
                                child: ListTile(
                                  leading: const Icon(
                                    Icons.book,
                                    color: Colors.blue,
                                  ),
                                  title: Text(b['title'] ?? '?'),
                                  subtitle: Text('$author · $year'),
                                ),
                              );
                            },
                          ),
                  ),
          ],
        ),
      ),
    );
  }
}
