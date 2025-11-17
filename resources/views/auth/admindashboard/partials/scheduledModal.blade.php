<div class="row g-3">


    <div class="col-sm-12 mb-2">
        <label class="form-label">Curriculum: Sy - Grade Level <span class="text-danger">*</span></label>
        
        <select name="curriculum_id" class="form-select" id="curriculum_id" required>
            <option value="">--SELECT--</option>
            @foreach(($result ?? collect()) as $s)
            <option value="{{ $s->id }}" >
                {{ $s->name }}
            </option>
            @endforeach
        </select>
    </div>

    <div class="col-sm-12" id="tableHere">


    </div>

</div>