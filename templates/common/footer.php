<footer>
    <div class="footer-content">
        <h3>Our Company</h3>
        <p>Company information here.</p>
        <form id="feedback-form" action="/submit-feedback" method="POST">
            <label for="feedback">Feedback:</label>
            <textarea id="feedback" name="feedback" required></textarea>
            <button type="submit">Submit</button>
        </form>
    </div>
    <script>
        document.getElementById('feedback-form').addEventListener('submit', function(event) {
            event.preventDefault();
            const feedback = document.getElementById('feedback').value;
            fetch('/submit-feedback', {
                method: 'POST',
                body: JSON.stringify({feedback}),
                headers: {
                    'Content-Type': 'application/json'
                }
            }).then(response => {
                if(response.ok) {
                    alert('Feedback submitted successfully!');
                } else {
                    alert('Error submitting feedback.');
                }
            });
        });
    </script>
</footer>