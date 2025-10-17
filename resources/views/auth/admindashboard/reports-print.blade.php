<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Enrollment Reports - Print</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body { padding: 20px; font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
    h4 { margin: 0 0 12px; }
    h5 { margin: 24px 0 8px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ccc; padding: 6px 8px; font-size: 12px; vertical-align: middle; }
    th { background: #f8f9fa; }
    .text-end { text-align: right; }
    .small { font-size: 12px; color: #666; }
    .mb-2 { margin-bottom: .5rem; }
    .mt-2 { margin-top: .5rem; }
    @media print {
      @page { size: A4 landscape; margin: 12mm; }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body>
  <div class="no-print mb-2">
    <button onclick="window.print()">Print</button>
  </div>

  <h4>Enrollment Reports</h4>

  @forelse($grouped as $grade => $rows)
    <h5>{{ $grade ?: '— No Grade —' }} <span class="small">({{ $rows->count() }})</span></h5>
    <table class="mb-2">
      <thead>
        <tr>
          <th>Student</th>
          <th>Guardian</th>
          <th>Contact</th>
          <th>School Year</th>
          <th class="text-end">Tuition</th>
          <th class="text-end">Optional</th>
          <th class="text-end">Total</th>
          <th class="text-end">Paid</th>
          <th class="text-end">Balance</th>
          <th>Enroll</th>
          <th>Pay</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($rows as $en)
          @php
            $s = $en->student ?? null;
            $g = ($en->guardian ?? null) ?: ($s?->guardian);
            $syText = $en->schoolyr?->school_year ?? '—';

            $base     = (float) ($s?->base_tuition ?? 0);
            $optional = (float) ($s?->optional_sum ?? 0);
            $total    = $base + $optional;
            $paid     = (float) ($s?->payments()->sum('amount') ?? 0);
            $balance  = max(0, round($total - $paid, 2));

            $guardianName = $g?->full_name ?: '—';
            $guardianContact = $g?->g_contact ?: ($g?->m_contact ?: ($g?->f_contact ?: ($g?->g_email ?: '—')));
          @endphp
          <tr>
            <td>{{ $s?->full_name ?? '—' }}</td>
            <td>{{ $guardianName }}</td>
            <td>{{ $guardianContact }}</td>
            <td>{{ $syText }}</td>
            <td class="text-end">{{ number_format($base, 2) }}</td>
            <td class="text-end">{{ number_format($optional, 2) }}</td>
            <td class="text-end">{{ number_format($total, 2) }}</td>
            <td class="text-end">{{ number_format($paid, 2) }}</td>
            <td class="text-end">{{ number_format($balance, 2) }}</td>
            <td>{{ $en->status ?? '' }}</td>
            <td>{{ $en->payment_status ?? '' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @empty
    <p class="small text-muted">No enrollments found for printing.</p>
  @endforelse
</body>
</html>
