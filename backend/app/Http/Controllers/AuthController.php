// In register() method validation rules:
$validated = $request->validate([
    // ...existing rules...
    'student_type' => 'required|in:daily,external',
]);
