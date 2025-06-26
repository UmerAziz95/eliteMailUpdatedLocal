<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Capacity Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        input[type="checkbox"] {
            margin-right: 8px;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
        }
        button:hover {
            background-color: #0056b3;
        }
        .test-button {
            background-color: #28a745;
        }
        .test-button:hover {
            background-color: #218838;
        }
        .output {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 4px;
            border-left: 4px solid #007bff;
            display: none;
        }
        .output.show {
            display: block;
        }
        .output h3 {
            margin-top: 0;
            color: #007bff;
        }
        .output pre {
            background-color: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 14px;
            line-height: 1.4;
        }
        .success {
            border-left-color: #28a745;
        }
        .success h3 {
            color: #28a745;
        }
        .error {
            border-left-color: #dc3545;
        }
        .error h3 {
            color: #dc3545;
        }
        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Panel Capacity Test</h1>
        
        <form id="testForm">
            <div class="form-group">
                <label>
                    <input type="checkbox" id="dryRun" name="dry_run" value="1">
                    Dry Run Mode (Don't send actual emails)
                </label>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" id="force" name="force" value="1">
                    Force Mode (Override daily limits)
                </label>
            </div>
            
            <button type="button" onclick="runTest()" class="test-button">
                🚀 Run Panel Capacity Check
            </button>
            
            <button type="button" onclick="clearOutput()">
                🗑️ Clear Output
            </button>
        </form>
        
        <div id="output" class="output">
            <h3>Test Results</h3>
            <div id="outputContent"></div>
        </div>
    </div>

    <script>
        function runTest() {
            const form = document.getElementById('testForm');
            const output = document.getElementById('output');
            const outputContent = document.getElementById('outputContent');
            const dryRun = document.getElementById('dryRun').checked;
            const force = document.getElementById('force').checked;
            
            // Show loading
            output.className = 'output show';
            output.innerHTML = '<div class="loading">⏳ Running panel capacity check...</div>';
            
            // Build URL with parameters
            const params = new URLSearchParams();
            if (dryRun) params.append('dry_run', '1');
            if (force) params.append('force', '1');
            
            const url = '/cron/test-panel-capacity?' + params.toString();
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    displayResults(data);
                })
                .catch(error => {
                    displayError('Network error: ' + error.message);
                });
        }
        
        function displayResults(data) {
            const output = document.getElementById('output');
            const successClass = data.success ? 'success' : 'error';
            const statusIcon = data.success ? '✅' : '❌';
            
            output.className = `output show ${successClass}`;
            
            let html = `
                <h3>${statusIcon} Test Results</h3>
                <p><strong>Command:</strong> ${data.command || 'N/A'}</p>
                <p><strong>Return Code:</strong> ${data.return_code || 'N/A'}</p>
                <p><strong>Timestamp:</strong> ${data.timestamp}</p>
            `;
            
            if (data.options) {
                html += `
                    <p><strong>Options:</strong></p>
                    <ul>
                        <li>Dry Run: ${data.options.dry_run ? 'Yes' : 'No'}</li>
                        <li>Force: ${data.options.force ? 'Yes' : 'No'}</li>
                    </ul>
                `;
            }
            
            if (data.output && data.output.length > 0) {
                html += `
                    <p><strong>Command Output:</strong></p>
                    <pre>${data.output.join('\n')}</pre>
                `;
            }
            
            if (data.error) {
                html += `
                    <p><strong>Error:</strong></p>
                    <pre>${data.error}</pre>
                `;
            }
            
            output.innerHTML = html;
        }
        
        function displayError(message) {
            const output = document.getElementById('output');
            output.className = 'output show error';
            output.innerHTML = `
                <h3>❌ Error</h3>
                <p>${message}</p>
            `;
        }
        
        function clearOutput() {
            const output = document.getElementById('output');
            output.className = 'output';
            output.innerHTML = '';
        }
    </script>
</body>
</html>
