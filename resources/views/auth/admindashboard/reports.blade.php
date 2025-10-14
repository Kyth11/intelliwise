@extends('layouts.admin')

@section('title', 'Enrollment Reports')

@push('styles')
<style>
  .table-sm td, .table-sm th { padding:.5rem .6rem }
  .thead-sticky th { position:sticky; top:0; z-index:1; background:var(--bs-body-bg) }
  .totals-row td { font-weight:600; border-top:2px solid #ddd }
  .filters .form-control, .filters .form-select { min-width:160px }
</style>
@endpush

@section('content')
<div class="container-fluid py-3">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h3 class="mb-0">Enrollment Reports</h3>
  </div>

  <form method="get" class="filters row g-2 align-items-end mb-3">
    <div class="col-auto">
      <label class="form-label">School Year (ID)</label>
      <input type="text" name="sy" value="{{ $sy }}" class="form-control" placeholder="e.g. 1 (2025-2026)">
    </div>
    <div class="col-auto">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="">All</option>
        @foreach (['Pending','Enrolled','Withdrawn','Not Enrolled'] as $st)
          <option value="{{ $st }}" @selected($status === $st)>{{ $st }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-auto">
      <label class="form-label">Search</label>
      <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="Student/Guardian/Contact">
    </div>
    <div class="col-auto">
      <button class="btn btn-primary"><i class="bi bi-funnel"></i> Apply</button>
      <a href="{{ route('admin.reports.enrollments') }}" class="btn btn-outline-secondary">Reset</a>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-striped table-hover table-sm align-middle">
      <thead class="thead-sticky">
        <tr>
          <th>#</th>
          <th>Student</th>
          <th>Grade Level</th>
          <th>Guardian</th>
          <th>Contact</th>
          <th>School Year</th>
          <th class="text-end">Tuition</th>
          <th class="text-end">Optional Fees</th>
          <th class="text-end">Total Due</th>
          <th class="text-end">Paid to Date</th>
          <th class="text-end">Balance</th>
          <th>Enroll Status</th>
          <th>Pay Status</th>
        </tr>
      </thead>
      <tbody>
      @php $rowNum = ($enrollments->currentPage()-1)*$enrollments->perPage(); @endphp

      @forelse ($enrollments as $en)
        @php
          $s      = $en->student;
          $g      = $en->guardian ?? ($s?->guardian);
          $grade  = $s?->gradelvl?->grade_level ?? $s?->s_gradelvl ?? '—';
          $syText = $en->schoolyr?->school_year ?? '—';

          // Live compute (truth)
          $base     = (float) ($s?->base_tuition ?? 0);
          $optional = (float) ($s?->optional_sum ?? 0);
          $total    = $base + $optional;
          $paid     = (float) ($s?->payments()->sum('amount') ?? 0);
          $balance  = max(0, round($total - $paid, 2));
        @endphp
        <tr>
          <td>{{ ++$rowNum }}</td>
          <td>{{ $s?->full_name ?? '—' }}</td>
          <td>{{ $grade }}</td>
          <td>{{ $g ? trim(($g->g_firstname ?? '').' '.($g->g_lastname ?? '')) : '—' }}</td>
          <td>{{ $g?->g_contact ?? $g?->g_email ?? '—' }}</td>
          <td>{{ $syText }}</td>
          <td class="text-end">{{ number_format($base, 2) }}</td>
          <td class="text-end">{{ number_format($optional, 2) }}</td>
          <td class="text-end">{{ number_format($total, 2) }}</td>
          <td class="text-end">{{ number_format($paid, 2) }}</td>
          <td class="text-end fw-semibold {{ $balance > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($balance, 2) }}</td>
          <td>{{ $en->status }}</td>
          <td>{{ $en->payment_status }}</td>
        </tr>
      @empty
        <tr><td colspan="13" class="text-center py-4">No enrollments found.</td></tr>
      @endforelse
      </tbody>

      <tfoot>
        <tr class="totals-row">
          <td colspan="6" class="text-end">Page Totals:</td>
          <td class="text-end">{{ number_format($pageTotals['tuition'], 2) }}</td>
          <td class="text-end">{{ number_format($pageTotals['optional'], 2) }}</td>
          <td class="text-end">{{ number_format($pageTotals['total_due'], 2) }}</td>
          <td class="text-end">{{ number_format($pageTotals['paid'], 2) }}</td>
          <td class="text-end">{{ number_format($pageTotals['balance'], 2) }}</td>
          <td colspan="2"></td>
        </tr>
      </tfoot>
    </table>
  </div>

  <div class="mt-2">
    {{ $enrollments->links() }}
  </div>
</div>
@endsection
