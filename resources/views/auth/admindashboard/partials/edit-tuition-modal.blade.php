{{-- resources/views/auth/admindashboard/partials/edit-tuition-modal.blade.php --}}
@if(isset($t))
    <!-- Edit Tuition Modal (per-row) -->
    <div class="modal fade" id="editTuitionModal{{ $t->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('tuitions.update', $t->id) }}" method="POST" id="editTuitionForm{{ $t->id }}">
                    @csrf
                    @method('PUT')

                    <div class="modal-header">
                        <h5 class="modal-title">Edit Tuition & Fees</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Grade Level</label>
                            <input type="text" name="grade_level" class="form-control"
                                value="{{ old('grade_level', $t->grade_level) }}" required>
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <label>Monthly Fee</label>
                                <input type="number" step="0.01" name="monthly_fee" id="edit_monthly_{{ $t->id }}"
                                    class="form-control" value="{{ old('monthly_fee', $t->monthly_fee) }}">
                            </div>
                            <div class="col-6">
                                <label>Yearly Fee</label>
                                <input type="number" step="0.01" name="yearly_fee" id="edit_yearly_{{ $t->id }}"
                                    class="form-control" value="{{ old('yearly_fee', $t->yearly_fee) }}">
                            </div>
                        </div>
                        <small class="text-muted d-block mb-2">Monthly/Yearly auto-sync (10 months).</small>

                        <div class="mb-3">
                            <label>Miscellaneous Fee (optional)</label>
                            <input type="number" step="0.01" name="misc_fee" id="edit_misc_{{ $t->id }}"
                                class="form-control" value="{{ old('misc_fee', $t->misc_fee) }}">
                        </div>

                        <div class="mb-3">
                            <label>Optional Fee Description</label>
                            <input type="text" name="optional_fee_desc" class="form-control"
                                value="{{ old('optional_fee_desc', $t->optional_fee_desc) }}">
                        </div>

                        <div class="mb-3">
                            <label>Optional Fee Amount (optional)</label>
                            <input type="number" step="0.01" name="optional_fee_amount" id="edit_optional_{{ $t->id }}"
                                class="form-control" value="{{ old('optional_fee_amount', $t->optional_fee_amount) }}">
                        </div>

                        <div class="mb-3">
                            <label>Total (computed)</label>
                            <input type="number" step="0.01" id="edit_total_{{ $t->id }}" class="form-control"
                                value="{{ number_format((float) $t->total_yearly, 2, '.', '') }}" readonly>
                        </div>

                        <div class="mb-3">
                            <label>School Year (optional)</label>
                            <select name="school_year" class="form-select">
                                <option value="">— None —</option>
                                @foreach($schoolyrs as $sy)
                                    <option value="{{ $sy->school_year }}" {{ (old('school_year', $t->school_year) === $sy->school_year) ? 'selected' : '' }}>
                                        {{ $sy->school_year }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-warning">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- /Edit Tuition Modal -->

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const MONTHS = 10;
            const monthly = document.getElementById('edit_monthly_{{ $t->id }}');
            const yearly = document.getElementById('edit_yearly_{{ $t->id }}');
            const misc = document.getElementById('edit_misc_{{ $t->id }}');
            const optAmt = document.getElementById('edit_optional_{{ $t->id }}');
            const total = document.getElementById('edit_total_{{ $t->id }}');

            function num(v) { const n = parseFloat(v); return isNaN(n) ? 0 : n; }
            let syncing = false;

            function fromMonthly() { if (syncing) return; syncing = true; if (yearly) yearly.value = (num(monthly.value) * MONTHS).toFixed(2); totalize(); syncing = false; }
            function fromYearly() { if (syncing) return; syncing = true; if (monthly) monthly.value = (num(yearly.value) / MONTHS).toFixed(2); totalize(); syncing = false; }
            function totalize() {
                const y = num(yearly?.value);
                const m = misc?.value === '' ? 0 : num(misc?.value);
                const o = optAmt?.value === '' ? 0 : num(optAmt?.value);
                if (total) total.value = (y + m + o).toFixed(2);
            }

            monthly && monthly.addEventListener('input', fromMonthly);
            yearly && yearly.addEventListener('input', fromYearly);
            misc && misc.addEventListener('input', totalize);
            optAmt && optAmt.addEventListener('input', totalize);

            totalize();
        });
    </script>
@endif
