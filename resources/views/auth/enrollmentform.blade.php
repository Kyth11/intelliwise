<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enrollment Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .form-container {
            border: 2px solid #000;
            padding: 20px;
            width: 800px;
            margin: auto;
            background: #fff;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            font-weight: bold;
            display: block;
        }
        input, select {
            width: 100%;
            padding: 6px;
            margin-top: 5px;
            border: 1px solid #ccc;
        }
        .print-btn {
            display: block;
            margin: 20px auto;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 6px;
        }
        .print-btn:hover {
            background: #0056b3;
        }
        /* Hide print button when printing */
        @media print {
            .print-btn {
                display: none;
            }
        }
    </style>
</head>
<body>

    <div class="form-container">
        <h2>Enrollment Form</h2>

        <div class="form-group">
            <label>Student Name:</label>
            <input type="text" value="{{ $student->name ?? '' }}">
        </div>

        <div class="form-group">
            <label>Grade Level:</label>
            <input type="text" value="{{ $student->grade ?? '' }}">
        </div>

        <div class="form-group">
            <label>Birthdate:</label>
            <input type="text" value="{{ $student->birthdate ?? '' }}">
        </div>

        <div class="form-group">
            <label>Parent/Guardian:</label>
            <input type="text" value="{{ $student->guardian ?? '' }}">
        </div>

        <div class="form-group">
            <label>Contact Number:</label>
            <input type="text" value="{{ $student->contact ?? '' }}">
        </div>

        <button class="print-btn" onclick="window.print()">Print Form</button>
    </div>

</body>
</html>
