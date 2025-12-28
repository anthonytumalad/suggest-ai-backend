<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <h1>CSRF Test</h1>
    <p>Token: {{ $csrf_token }}</p>
    <p>Session ID: {{ $session_id }}</p>
    
    <form action="/test-csrf-submit" method="POST">
        @csrf
        <button type="submit">Test Submit</button>
    </form>
    
    <script>
        // Also test AJAX
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            fetch('/test-csrf-submit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({test: 'data'})
            })
            .then(response => response.json())
            .then(data => console.log('AJAX Response:', data));
        });
    </script>
</body>
</html>