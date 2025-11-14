<!-- Optional Fees Modal -->
<div class="modal fade" id="optionalFeesModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title">Optional Fees (Master List)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        {{-- Add new --}}
        <form action="{{ route('optionalfees.store') }}" method="POST" class="border rounded p-3 mb-3">
          @csrf
          <div class="row g-2 align-items-end">
            <div class="col-md-4">
              <label class="form-label">Name</label>
              <input type="text" name="name" class="form-control" placeholder="e.g., ID / Insurance" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Amount (₱)</label>
              <input type="number" step="0.01" min="0" name="amount" class="form-control" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Scope</label>
              <select name="scope" class="form-select">
                <option value="both">Both (grade & student)</option>
                <option value="grade">Grade-level</option>
                <option value="student">Student-level</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label d-block">Active</label>
              {{-- Hidden 0 ensures “unchecked” posts as 0 --}}
              <input type="hidden" name="active" value="0">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="active" id="optFeeActive" value="1" checked>
                <label class="form-check-label" for="optFeeActive">Yes</label>
              </div>
            </div>
          </div>
          <div class="mt-3">
            <button class="btn btn-primary btn-sm">Add Fee</button>
          </div>
        </form>

        {{-- List existing --}}
        <div class="table-responsive">
          <table class="table table-bordered table-striped align-middle">
            <thead class="table-light">
              <tr>
                <th>Name</th>
                <th>Amount (₱)</th>
                <th>Scope</th>
                <th>Active</th>
                <th class="text-nowrap">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse(($optionalFees ?? collect()) as $fee)
                <tr>
                  <td>
                    <form action="{{ route('optionalfees.update', $fee->id) }}" method="POST" class="row g-2 align-items-center">
                      @csrf
                      @method('PUT')

                      <div class="col-12 col-md-4">
                        <input type="text" name="name" class="form-control form-control-sm" value="{{ $fee->name }}" required>
                      </div>

                      <div class="col-12 col-md-3">
                        <input type="number" step="0.01" min="0" name="amount" class="form-control form-control-sm" value="{{ number_format($fee->amount, 2, '.', '') }}" required>
                      </div>

                      <div class="col-12 col-md-3">
                        <select name="scope" class="form-select form-select-sm">
                          <option value="both"   {{ $fee->scope === 'both' ? 'selected' : '' }}>Both</option>
                          <option value="grade"  {{ $fee->scope === 'grade' ? 'selected' : '' }}>Grade-level</option>
                          <option value="student"{{ $fee->scope === 'student' ? 'selected' : '' }}>Student-level</option>
                        </select>
                      </div>

                      <div class="col-6 col-md-1">
                        {{-- Hidden 0 ensures unchecked posts 0 --}}
                        <input type="hidden" name="active" value="0">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="active" id="feeActive{{ $fee->id }}" value="1" {{ $fee->active ? 'checked' : '' }}>
                          <label class="form-check-label" for="feeActive{{ $fee->id }}">Active</label>
                        </div>
                      </div>

                      <div class="col-6 col-md-1 text-end">
                        <button class="btn btn-warning btn-sm">Save</button>
                      </div>
                    </form>
                  </td>

                  <td class="d-none d-md-table-cell">{{ number_format($fee->amount, 2) }}</td>
                  <td class="d-none d-md-table-cell text-capitalize">{{ $fee->scope }}</td>
                  <td class="d-none d-md-table-cell">{{ $fee->active ? 'Yes' : 'No' }}</td>

                  <td class="text-nowrap">
                    <form action="{{ route('optionalfees.destroy', $fee->id) }}" method="POST" class="d-inline js-confirm-delete" data-confirm="Delete this optional fee?">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-danger btn-sm js-delete-btn"><i class="bi bi-archive"></i></button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-muted">No optional fees yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>
