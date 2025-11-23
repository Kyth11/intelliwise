{{-- resources/views/emails/cor.blade.php --}}
@php
    /** @var \App\Models\Student $student */
    /** @var \App\Models\Guardian|null $guardian */
    /** @var array $billing  – computed fees and subjects  */
    /** @var string $schoolYear */
    /** @var string|null $semester */
    /** @var string|null $courseYear */
    /** @var string|null $registrationNo */
    /** @var string $signerName */

    $fullName = trim(implode(' ', array_filter([
        $student->s_firstname ?? '',
        $student->s_middlename ?? '',
        $student->s_lastname ?? '',
    ])));

    $dob       = \Illuminate\Support\Carbon::parse($student->s_birthdate)->format('F d, Y');
    $printedAt = now()->format('F d, Y');

    // Fees pulled from $billing
    $tuitionFee     = (float) ($billing['tuition_fee'] ?? 0);
    $miscFee        = (float) ($billing['misc_fee'] ?? 0);
    $enrollmentFee  = (float) ($billing['enrollment_fee'] ?? 0);
    $optionalItems  = $billing['optional_fees'] ?? [];
    $optionalTotal  = (float) ($billing['other_fees'] ?? 0);
    $totalFees      = (float) ($billing['total_school_fees']
                        ?? ($tuitionFee + $miscFee + $enrollmentFee + $optionalTotal));
@endphp

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>CERTIFICATE OF REGISTRATION</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 0;
        }
        .cor-wrapper {
            width: 800px;
            margin: 0 auto;
            padding: 16px 24px;
            border: 1px solid #000;
        }
        .cor-header {
            text-align: center;
        }
        .cor-header h3 {
            margin: 0;
            font-size: 16px;
        }
        .cor-header h4 {
            margin: 0;
            font-size: 14px;
        }
        .cor-sub {
            font-size: 10px;
        }
        .cor-meta {
            margin-top: 8px;
            font-size: 10px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        .meta-table td {
            padding: 2px 4px;
            vertical-align: top;
            font-size: 10px;
        }
        .meta-table.meta-three-col td:nth-child(1) {
            width: 33%;
        }
        .meta-table.meta-three-col td:nth-child(2) {
            width: 33%;
            padding-left: 24px;
        }
        .meta-table.meta-three-col td:nth-child(3) {
            width: 34%;
        }

        .subjects-table th,
        .subjects-table td {
            border: 1px solid #000;
            padding: 3px 4px;
            font-size: 10px;
        }
        .subjects-table th {
            text-align: center;
        }

        .subjects-table .col-subject {
            width: 46%;
        }
        .subjects-table .col-day {
            width: 16%;
            text-align: center;
        }
        .subjects-table .col-time {
            width: 20%;
            text-align: center;
        }
        .subjects-table .col-teacher {
            width: 18%;
        }

        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }

        .fees-table td {
            padding: 2px 4px;
            font-size: 10px;
        }
        .signature-block {
            margin-top: 24px;
            display: flex;
            justify-content: flex-end;
        }
        .signature-inner {
            text-align: center;
            font-size: 10px;
        }
        .sig-line {
            margin-top: 32px;
            border-top: 1px solid #000;
            padding-top: 2px;
            width: 220px;
        }
    </style>
</head>
<body>
<div class="cor-wrapper">
    <div class="cor-header">
        <h3>INTELLIWISE GRACE CHRISTIAN ACADEMY</h3>
        <div class="cor-sub">Zone 3, Bonbon, Opol Misamis Oriental</div>
        <div class="cor-sub">Contact No. +639161808738</div>
        <h4 style="margin-top:8px;">CERTIFICATE OF REGISTRATION</h4>
    </div>

    <table class="meta-table meta-three-col" style="margin-top:8px;">
        <tr>
            <td><strong>Registration No.</strong> {{ $registrationNo ?? '—' }}</td>
            <td><strong>Semester:</strong> {{ $semester ?? 'Full Year' }}</td>
            <td><strong>School Year:</strong> {{ $schoolYear }}</td>
        </tr>
        <tr>
            <td><strong>Date Enrolled:</strong> {{ $billing['date_enrolled']->format('F d, Y') }}</td>
            <td><strong>Grade Level:</strong> {{ $courseYear }}</td>
            <td><strong>Student Type:</strong> {{ ucfirst($student->enroll_type ?? 'New') }}</td>
        </tr>
    </table>

    <table class="meta-table" style="margin-top:8px;">
        <tr>
            <td colspan="2"><strong>Student Name:</strong> {{ strtoupper($fullName) }}</td>
            <td><strong>LRN:</strong> {{ $student->lrn }}</td>
        </tr>
        <tr>
            <td><strong>Date of Birth:</strong> {{ $dob }}</td>
            <td><strong>Gender:</strong> {{ $student->s_gender ?? '—' }}</td>
            <td><strong>Contact No.:</strong> {{ $student->s_contact ?? '—' }}</td>
        </tr>
        <tr>
            <td colspan="3"><strong>Address:</strong> {{ $student->s_address ?? '—' }}</td>
        </tr>
        @if($guardian)
            <tr>
                <td colspan="3">
                    <strong>Parent / Guardian:</strong>
                    {{ $guardian->display_name ?? ($guardian->g_firstname ?? '') . ' ' . ($guardian->g_lastname ?? '') }}
                    @if(!empty($guardian->g_contact))
                        | Contact: {{ $guardian->g_contact }}
                    @endif
                </td>
            </tr>
        @endif
    </table>

    <h5 style="margin-top:12px; margin-bottom:4px;">SUBJECTS / CLASS SCHEDULE</h5>

    <table class="subjects-table">
        <thead>
        <tr>
            <th class="col-subject">Subjects</th>
            <th class="col-day">Day</th>
            <th class="col-time">Time</th>
            <th class="col-teacher">Teacher</th>
        </tr>
        </thead>
        <tbody>
        @forelse($billing['subjects'] as $subj)
            @php
                $daysRaw  = $subj['day']  ?? '';
                $timeRaw  = $subj['time'] ?? '';

                $days  = array_values(array_filter(array_map('trim', explode('/', $daysRaw))));
                $times = array_values(array_filter(array_map('trim', explode(',', $timeRaw))));

                $rowsCount = max(count($days), count($times), 1);
            @endphp

            @for($i = 0; $i < $rowsCount; $i++)
                <tr>
                    @if($i === 0)
                        <td>{{ $subj['title'] }}</td>
                    @else
                        <td></td>
                    @endif

                    <td class="text-center">
                        {{ $days[$i] ?? '' }}
                    </td>
                    <td class="text-center">
                        {{ $times[$i] ?? '' }}
                    </td>

                    @if($i === 0)
                        <td rowspan="{{ $rowsCount }}">
                            {{ $subj['teacher'] }}
                        </td>
                    @endif
                </tr>
            @endfor
        @empty
            <tr>
                <td colspan="4" class="text-center">No subjects configured for this grade level.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <table class="fees-table" style="margin-top:12px;">
        <tr>
            <td style="width:60%;"><strong>ASSESSMENT:</strong></td>
            <td></td>
        </tr>

        <tr>
            <td>Tuition Fee</td>
            <td class="text-right">₱ {{ number_format($tuitionFee, 2) }}</td>
        </tr>

        <tr>
            <td>Miscellaneous Fees</td>
            <td class="text-right">₱ {{ number_format($miscFee, 2) }}</td>
        </tr>

        @if($enrollmentFee > 0)
            <tr>
                <td>Enrollment / Registration Fee</td>
                <td class="text-right">₱ {{ number_format($enrollmentFee, 2) }}</td>
            </tr>
        @endif

        @if(count($optionalItems) > 0)
            <tr>
                <td colspan="2"><strong>Optional Fees</strong></td>
            </tr>

            @foreach($optionalItems as $item)
                @php
                    $itemName   = is_array($item) ? ($item['name'] ?? '') : ($item->name ?? '');
                    $itemAmount = is_array($item)
                        ? (float) ($item['amount'] ?? 0)
                        : (float) ($item->amount ?? 0);
                @endphp
                <tr>
                    <td>&nbsp;&nbsp;{{ $itemName }}</td>
                    <td class="text-right">
                        ₱ {{ number_format($itemAmount, 2) }}
                    </td>
                </tr>
            @endforeach

            <tr>
                <td><strong>Total Optional Fees</strong></td>
                <td class="text-right">
                    ₱ {{ number_format($optionalTotal, 2) }}
                </td>
            </tr>
        @elseif($optionalTotal > 0)
            <tr>
                <td>Other / Optional Fees</td>
                <td class="text-right">₱ {{ number_format($optionalTotal, 2) }}</td>
            </tr>
        @endif

        <tr>
            <td><strong>Total School Fees</strong></td>
            <td class="text-right"><strong>₱ {{ number_format($totalFees, 2) }}</strong></td>
        </tr>
    </table>

    <div style="margin-top:8px; font-size:9px;">
        The subjects reflected here are based on the prescribed curriculum for the grade level currently enrolled.
    </div>

    <div class="signature-block">
        <div class="signature-inner">
            <div class="sig-line">{{ strtoupper($signerName) }}</div>
            <div>Program Head / Registrar</div>
        </div>
    </div>

    <div style="margin-top:16px; font-size:9px;">
        Printed on {{ $printedAt }}.
    </div>
</div>
</body>
</html>
