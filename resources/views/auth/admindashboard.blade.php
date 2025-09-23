<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dash.css') }}">
</head>

<body>
    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <img src="{{ asset('images/Intelliwise.png') }}" alt="Logo" id="logo">
            <h4>Admin Dashboard</h4>
            <a href="#" class="sidebar-link active" data-section="dashboard"><i class="bi bi-speedometer2"></i><span>
                    Dashboard</span></a>
            <a href="#" class="sidebar-link" data-section="students"><i class="bi bi-people"></i><span>
                    Students</span></a>
            <a href="#" class="sidebar-link" data-section="faculty"><i class="bi bi-person-badge"></i><span>
                    Faculty</span></a>
            <a href="#" class="sidebar-link" data-section="settings"><i class="bi bi-gear"></i><span>
                    Settings</span></a>
            <a href="#" class="sidebar-link" data-section="accounts"><i class="bi bi-person-gear"></i><span>
                    Manage Accounts</span></a>
            <a href="{{ route('login') }}"><i class="bi bi-box-arrow-right"></i><span> Logout</span></a>
        </div>

        <!-- Content -->
        <div class="content" id="content">
            <!-- Default: Dashboard -->
            <div id="dashboard-section" class="section ">
                <!-- Topbar -->
                <div class="topbar">
                   <h3 class="mb-0">
              Welcome,
              @if(Auth::check())
                  {{ Auth::user()->name }}
              @else
                  Faculty
              @endif
              !
          </h3>
                </div>

                <!-- Dashboard Cards -->
                <div class="row">
                    <div class="col-md-4 mb-4 ">
                        <div class="card p-4 text-center">
                            <h5>Total Students</h5>
                            <h2>{{ $students->count() }}</h2>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card p-4 text-center">
                            <h5>Total Teachers</h5>
                            <h2>3</h2>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card p-4 text-center">
                            <h5>System Users</h5>
                            <h2>4</h2>
                        </div>
                    </div>
                </div>

                <!-- Announcements -->
                <div class="card p-4 mb-4">
                    <h5>Announcements</h5>
                    <p>No announcements yet.</p>
                </div>

                <!-- Enroll Student Section -->
                <div class="card p-4 text-center">
                    <h5>Enroll a Student</h5>
                    <p>You can add a new student to the system.</p>
                    <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#enrollModal">
                        <i class="bi bi-person-plus me-2"></i> Enroll Now
                    </a>
                </div>
            </div>

            <!-- Students Section -->
            <div id="students-section" class="card section d-none">
                <h4>Manage Students</h4>
                <p>Here you can view and manage student records.</p>

                <div class="card p-4">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <table class="table table-bordered table-striped">
                        <thead class="table-primary">
                            <tr>
                                <th>Student No.</th>
                                <th>Full Name</th>
                                <th>Grade Level</th>
                                <th>Birthdate</th>
                                <th>Address</th>
                                <th>Contact</th>
                                <th>Email</th>
                                <th>Guardian</th>
                                <th>Guardian Email</th>
                                <th>Tuition</th>
                                <th>Payment Status</th>
                                <th>Enrollment Status</th>
                                <th>Date Enrolled</th>
                                <th>Tools</th> <!-- NEW -->
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($students as $student)
                                <tr>
                                    <td>{{ $student->id }}</td>
                                    <td>
                                        {{ $student->s_firstname }}
                                        @if ($student->s_middlename)
                                            {{ $student->s_middlename }}
                                        @endif
                                        {{ $student->s_lastname }}
                                    </td>
                                    <td>{{ $student->s_gradelvl }}</td>
                                    <td>{{ $student->s_birthdate }}</td>
                                    <td>{{ $student->s_address }}</td>
                                    <td>{{ $student->s_contact }}</td>
                                    <td>{{ $student->s_email ?? '-' }}</td>
                                    <td>
                                        {{ $student->s_guardianfirstname }} {{ $student->s_guardianlastname }}
                                        ({{ $student->s_guardiancontact }})
                                    </td>
                                    <td>{{ $student->s_guardianemail ?? '-' }}</td>
                                    <td>{{ $student->s_tuition_sum ?? '-' }}</td>
                                    <td>{{ $student->payment_status ?? '-' }}</td>
                                    <td>
                                        @if ($student->enrollment_status == 'Enrolled')
                                            <span class="badge bg-success">Enrolled</span>
                                        @else
                                            <span class="badge bg-secondary">Not Enrolled</span>
                                        @endif
                                    </td>
                                    <td>{{ $student->created_at->format('Y-m-d') }}</td>
                                    <td>
                                        <!-- Edit Button -->
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                            data-bs-target="#editStudentModal" data-id="{{ $student->id }}"
                                            data-firstname="{{ $student->s_firstname }}"
                                            data-middlename="{{ $student->s_middlename }}"
                                            data-lastname="{{ $student->s_lastname }}"
                                            data-gradelvl="{{ $student->s_gradelvl }}"
                                            data-birthdate="{{ $student->s_birthdate }}"
                                            data-address="{{ $student->s_address }}"
                                            data-contact="{{ $student->s_contact }}" data-email="{{ $student->s_email }}"
                                            data-guardianfirstname="{{ $student->s_guardianfirstname }}"
                                            data-guardianlastname="{{ $student->s_guardianlastname }}"
                                            data-guardiancontact="{{ $student->s_guardiancontact }}"
                                            data-guardianemail="{{ $student->s_guardianemail }}"
                                            data-tuition="{{ $student->s_tuition_sum }}"
                                            data-status="{{ $student->enrollment_status }}"
                                            data-payment="{{ $student->payment_status }}">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>

                                        <!-- Archive Button -->
                                        <form action="{{ route('students.destroy', $student->id) }}" method="POST"
                                            class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="bi bi-archive"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="14" class="text-center">No students found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>



            <!-- Faculty Section -->
            <div id="faculty-section" class=" card section d-none">
                <h4>Faculty Management</h4>
                <p>Here you can view and manage faculty details.</p>
            </div>

            <!-- Settings Section -->
            <div id="settings-section" class="card section d-none">
                <h4>System Settings</h4>
                <p>Manage system preferences and configurations here.</p>
            </div>

            <!-- Accounts Section -->
            <div id="accounts-section" class="card section d-none p-4">
                <h4>Manage Accounts</h4>
                <p>Here you can add, edit, or delete system users.</p>

                <div class="d-flex gap-2 mb-3">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFacultyModal">
                        <i class="bi bi-person-badge me-2"></i> Add Faculty Account
                    </button>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addGuardianModal">
                        <i class="bi bi-people-fill me-2"></i> Add Guardian Account
                    </button>
                </div>

                {{-- Success/Error Messages --}}
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
            </div>

            <!-- Add Faculty Modal -->
            <div class="modal fade" id="addFacultyModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form action="{{ route('faculty.store') }}" method="POST">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">Add Faculty Account</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <input type="text" name="f_firstname" class="form-control"
                                            placeholder="First Name" required>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" name="f_middlename" class="form-control"
                                            placeholder="Middle Name">
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" name="f_lastname" class="form-control"
                                            placeholder="Last Name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" name="f_address" class="form-control" placeholder="Address"
                                            required>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" name="f_contact" class="form-control"
                                            placeholder="Contact Number" required>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="email" name="f_email" class="form-control" placeholder="Email"
                                            required>
                                    </div>

                                    <!-- LOGIN DETAILS -->
                                    <div class="col-md-6">
                                        <input type="text" name="username" class="form-control" placeholder="Username"
                                            required>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="password" name="password" class="form-control"
                                            placeholder="Password" required>
                                    </div>

                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-success"><i class="bi bi-check-circle me-2"></i>
                                    Save</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Add Guardian Modal -->
            <div class="modal fade" id="addGuardianModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form action="{{ route('guardian.store') }}" method="POST">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">Add Guardian Account</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <input type="text" name="g_firstname" class="form-control"
                                            placeholder="First Name" required>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" name="g_middlename" class="form-control"
                                            placeholder="Middle Name">
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" name="g_lastname" class="form-control"
                                            placeholder="Last Name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" name="g_address" class="form-control" placeholder="Address"
                                            required>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" name="g_contact" class="form-control"
                                            placeholder="Contact Number" required>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="email" name="g_email" class="form-control" placeholder="Email"
                                            required>
                                    </div>

                                    <!-- LOGIN DETAILS -->
                                    <div class="col-md-6">
                                        <input type="text" name="username" class="form-control" placeholder="Username"
                                            required>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="password" name="password" class="form-control"
                                            placeholder="Password" required>
                                    </div>

                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-success"><i class="bi bi-check-circle me-2"></i>
                                    Save</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Enroll Student Modal -->
            <div class=" modal fade" id="enrollModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form action="{{ route('students.store') }}" method="POST">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">Enroll New Student</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">
                                {{-- Success/Error Messages --}}
                                @if (session('success'))
                                    <div class="alert alert-success">{{ session('success') }}</div>
                                @endif
                                @if (session('error'))
                                    <div class="alert alert-danger">{{ session('error') }}</div>
                                @endif
                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <div class="row g-3">
                                    <h6>Student Information</h6>
                                    <div class="col-md-4"><input type="text" name="s_firstname" class="form-control"
                                            placeholder="First Name" required></div>
                                    <div class="col-md-4"><input type="text" name="s_middlename" class="form-control"
                                            placeholder="Middle Name"></div>
                                    <div class="col-md-4"><input type="text" name="s_lastname" class="form-control"
                                            placeholder="Last Name" required></div>
                                    <div class="col-md-6"><input type="date" name="s_birthdate" class="form-control"
                                            required></div>
                                    <div class="col-md-6"><input type="text" name="s_address" class="form-control"
                                            placeholder="Address" required></div>
                                    <div class="col-md-6"><input type="text" name="s_contact" class="form-control"
                                            placeholder="Contact Number" required></div>
                                    <div class="col-md-6"><input type="email" name="s_email" class="form-control"
                                            placeholder="Email (optional)"></div>

                                    <h6 class="mt-3">Guardian Information</h6>
                                    <div class="col-md-4"><input type="text" name="s_guardianfirstname"
                                            class="form-control" placeholder="First Name" required></div>
                                    <div class="col-md-4"><input type="text" name="s_guardianlastname"
                                            class="form-control" placeholder="Last Name" required></div>
                                    <div class="col-md-4"><input type="text" name="s_guardiancontact"
                                            class="form-control" placeholder="Contact Number" required></div>
                                    <div class="col-md-6"><input type="email" name="s_guardianemail"
                                            class="form-control" placeholder="Email (optional)"></div>

                                    <h6 class="mt-3">Enrollment Details</h6>
                                    <div class="col-md-6">
                                        <select name="s_gradelvl" class="form-control">
                                            <option value="">Select Grade Level</option>
                                            <option value="Pre-Schooler">Pre-Schooler</option>
                                            <option value="Nursery">Nursery</option>
                                            <option value="Kindergarten">Kindergarten</option>
                                            <option value="Grade 1">Grade 1</option>
                                            <option value="Grade 2">Grade 2</option>
                                            <option value="Grade 3">Grade 3</option>
                                            <option value="Grade 4">Grade 4</option>
                                            <option value="Grade 5">Grade 5</option>
                                            <option value="Grade 6">Grade 6</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <select name="enrollment_status" class="form-control">
                                            <option value="Enrolled">Enrolled</option>
                                            <option value="Not Enrolled" selected>Not Enrolled</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="submit" class="btn btn-success"><i class="bi bi-check-circle me-2"></i>
                                    Save
                                    Student</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- Edit Student Modal -->
            <div class="modal fade" id="editStudentModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form id="editStudentForm" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Student</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">
                                <div class="row g-3">
                                    <h6>Student Information</h6>
                                    <div class="col-md-4"><input type="text" id="edit_firstname" name="s_firstname"
                                            class="form-control" required></div>
                                    <div class="col-md-4"><input type="text" id="edit_middlename" name="s_middlename"
                                            class="form-control"></div>
                                    <div class="col-md-4"><input type="text" id="edit_lastname" name="s_lastname"
                                            class="form-control" required></div>
                                    <div class="col-md-6"><input type="date" id="edit_birthdate" name="s_birthdate"
                                            class="form-control" required></div>
                                    <div class="col-md-6"><input type="text" id="edit_address" name="s_address"
                                            class="form-control" required></div>
                                    <div class="col-md-6"><input type="text" id="edit_contact" name="s_contact"
                                            class="form-control" required></div>
                                    <div class="col-md-6"><input type="email" id="edit_email" name="s_email"
                                            class="form-control"></div>

                                    <h6 class="mt-3">Guardian Information</h6>
                                    <div class="col-md-4"><input type="text" id="edit_guardianfirstname"
                                            name="s_guardianfirstname" class="form-control" required></div>
                                    <div class="col-md-4"><input type="text" id="edit_guardianlastname"
                                            name="s_guardianlastname" class="form-control" required></div>
                                    <div class="col-md-4"><input type="text" id="edit_guardiancontact"
                                            name="s_guardiancontact" class="form-control" required></div>
                                    <div class="col-md-6"><input type="email" id="edit_guardianemail"
                                            name="s_guardianemail" class="form-control"></div>

                                    <h6 class="mt-3">Enrollment Details</h6>
                                    <div class="col-md-6">
                                        <select id="edit_gradelvl" name="s_gradelvl" class="form-control">
                                            <option value="Pre-Schooler">Pre-Schooler</option>
                                            <option value="Nursery">Nursery</option>
                                            <option value="Kindergarten">Kindergarten</option>
                                            <option value="Grade 1">Grade 1</option>
                                            <option value="Grade 2">Grade 2</option>
                                            <option value="Grade 3">Grade 3</option>
                                            <option value="Grade 4">Grade 4</option>
                                            <option value="Grade 5">Grade 5</option>
                                            <option value="Grade 6">Grade 6</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6"><input type="text" id="edit_tuition" name="s_tuition_sum"
                                            class="form-control"></div>
                                    <div class="col-md-6">
                                        <select id="edit_status" name="enrollment_status" class="form-control">
                                            <option value="Enrolled">Enrolled</option>
                                            <option value="Not Enrolled">Not Enrolled</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6"><input type="text" id="edit_payment" name="payment_status"
                                            class="form-control"></div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="submit" class="btn btn-success"><i class="bi bi-check-circle me-2"></i>
                                    Save
                                    Changes</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>


            <!-- Bootstrap JS -->
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
            <script>
                const editStudentModal = document.getElementById('editStudentModal');
                editStudentModal.addEventListener('show.bs.modal', event => {
                    const button = event.relatedTarget;

                    // Get data attributes
                    const id = button.getAttribute('data-id');
                    const firstname = button.getAttribute('data-firstname');
                    const middlename = button.getAttribute('data-middlename');
                    const lastname = button.getAttribute('data-lastname');
                    const gradelvl = button.getAttribute('data-gradelvl');
                    const birthdate = button.getAttribute('data-birthdate');
                    const address = button.getAttribute('data-address');
                    const contact = button.getAttribute('data-contact');
                    const email = button.getAttribute('data-email');
                    const guardianfirstname = button.getAttribute('data-guardianfirstname');
                    const guardianlastname = button.getAttribute('data-guardianlastname');
                    const guardiancontact = button.getAttribute('data-guardiancontact');
                    const guardianemail = button.getAttribute('data-guardianemail');
                    const tuition = button.getAttribute('data-tuition');
                    const status = button.getAttribute('data-status');
                    const payment = button.getAttribute('data-payment');

                    // Fill form
                    document.getElementById('editStudentForm').action = `/students/${id}`;
                    document.getElementById('edit_firstname').value = firstname;
                    document.getElementById('edit_middlename').value = middlename;
                    document.getElementById('edit_lastname').value = lastname;
                    document.getElementById('edit_gradelvl').value = gradelvl;
                    document.getElementById('edit_birthdate').value = birthdate;
                    document.getElementById('edit_address').value = address;
                    document.getElementById('edit_contact').value = contact;
                    document.getElementById('edit_email').value = email;
                    document.getElementById('edit_guardianfirstname').value = guardianfirstname;
                    document.getElementById('edit_guardianlastname').value = guardianlastname;
                    document.getElementById('edit_guardiancontact').value = guardiancontact;
                    document.getElementById('edit_guardianemail').value = guardianemail;
                    document.getElementById('edit_tuition').value = tuition;
                    document.getElementById('edit_status').value = status;
                    document.getElementById('edit_payment').value = payment;
                });
            </script>
            <script>
                setTimeout(() => {
                    let alert = document.querySelector('.alert');
                    if (alert) {
                        let bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    }
                }, 3000); // hides after 3 seconds
            </script>


            <script>
                // Sidebar toggle
                const sidebar = document.getElementById('sidebar');
                const logo = document.getElementById('logo');
                logo.addEventListener('click', () => {
                    sidebar.classList.toggle('collapsed');
                });

                // Section switching
                const links = document.querySelectorAll('.sidebar-link');
                const sections = document.querySelectorAll('.section');

                links.forEach(link => {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();

                        // Remove active from all
                        links.forEach(l => l.classList.remove('active'));
                        link.classList.add('active');

                        // Hide all sections
                        sections.forEach(sec => sec.classList.add('d-none'));

                        // Show the selected section
                        const sectionId = link.getAttribute('data-section') + "-section";
                        document.getElementById(sectionId).classList.remove('d-none');
                    });
                });

                // Auto-open enroll modal if validation errors exist
                @if (session('modal') == 'enrollModal' || $errors->any())
                    var enrollModal = new bootstrap.Modal(document.getElementById('enrollModal'));
                    enrollModal.show();
                @endif




            </script>

</body>

</html>
