@extends('layouts.admin')

@section('title', 'System Settings')

@section('content')
<div class="card section p-4">
    <h4>System Settings</h4>
    <p>Manage system preferences and configurations here.</p>

    <div class="card mt-3 p-3">
        <ul class="list-group">
            <li class="list-group-item">Change Password</li>
            <li class="list-group-item">System Preferences</li>
            <li class="list-group-item">Backup Database</li>
        </ul>
    </div>
</div>
@endsection
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function() {
            const form = this.closest('form');
            Swal.fire({
                title: 'Are you sure?',
                text: "This announcement will be deleted!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                reverseButtons: true,
                // Floating style
                background: '#fff', // modal background
                backdrop: false, // disable dark overlay
                allowOutsideClick: true, // optional: allow clicking outside to dismiss
                allowEscapeKey: true
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
