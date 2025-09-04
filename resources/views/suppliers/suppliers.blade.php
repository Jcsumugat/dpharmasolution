<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Suppliers - MJ's Pharmacy</title>
    <link rel="stylesheet" href="{{ asset('css/suppliers.css') }}">
</head>

<body>
    @include('admin.admin-header')

    @if (session('success'))
        <div class="alert alert-success" id="flashMessage">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="container fade-in" id="mainContent">
        <div class="header-bar">
            <h2 class="page-title">Supplier Management</h2>
            <button class="btn btn-create" onclick="openModal()">Add Supplier</button>
        </div>

        <div class="table-wrapper">
            @if ($suppliers->isEmpty())
                <div class="no-suppliers">
                    <div style="font-size: 3rem; opacity: 0.3; margin-bottom: 1rem;">📦</div>
                    <div>No suppliers available.</div>
                    <div style="font-size: 0.875rem; opacity: 0.7; margin-top: 0.5rem;">Add your first supplier to get
                        started</div>
                </div>
            @else
                <table class="inventory-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact Person</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($suppliers as $supplier)
                            <tr>
                                <td>{{ $supplier->name }}</td>
                                <td>{{ $supplier->contact_person ?? '-' }}</td>
                                <td>{{ $supplier->phone ?? '-' }}</td>
                                <td>{{ $supplier->email ?? '-' }}</td>
                                <td title="{{ $supplier->address }}">{{ $supplier->address ?? '-' }}</td>
                                <td>
                                    <div class="dropdown">
                                        <button class="dropdown-toggle" onclick="toggleDropdown(event)"
                                            aria-label="Actions">&#8943;</button>
                                        <div class="dropdown-menu">
                                            <button class="dropdown-item"
                                                onclick='editSupplier(@json($supplier))'>
                                                Edit
                                            </button>
                                            <form action="{{ route('suppliers.destroy', $supplier->id) }}"
                                                method="POST"
                                                onsubmit="return confirm('Are you sure you want to delete this supplier?')"
                                                style="margin: 0;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item delete-btn">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <!-- Modal -->
    <div class="modal-bg" id="supplierModal">
        <div class="modal fade-in">
            <div class="modal-close" onclick="closeModal()" aria-label="Close modal">&times;</div>
            <div class="modal-header" id="modalTitle">Add Supplier</div>

            <form method="POST" id="supplierForm" action="{{ route('suppliers.store') }}">
                @csrf
                <div class="form-group">
                    <input type="text" name="name" id="name" placeholder=" " required>
                    <label for="name">Supplier Name *</label>
                </div>

                <div class="form-group">
                    <input type="text" name="contact_person" id="contact_person" placeholder=" ">
                    <label for="contact_person">Contact Person</label>
                </div>

                <div class="form-group">
                    <input type="tel" name="phone" id="phone" placeholder=" ">
                    <label for="phone">Phone Number</label>
                </div>

                <div class="form-group">
                    <input type="email" name="email" id="email" placeholder=" ">
                    <label for="email">Email Address</label>
                </div>

                <div class="form-group">
                    <textarea name="address" id="address" placeholder=" " rows="4"></textarea>
                    <label for="address">Complete Address</label>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="btn btn-cancel1" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-create1" id="submitBtn">Save Supplier</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Dropdown functionality
        function toggleDropdown(event) {
            event.stopPropagation();
            const dropdown = event.currentTarget.parentElement;
            const menu = dropdown.querySelector('.dropdown-menu');

            // Close all other dropdowns
            document.querySelectorAll('.dropdown-menu').forEach(d => {
                if (d !== menu) d.style.display = 'none';
            });

            // Toggle current dropdown
            menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';

            // Add active class for z-index management
            if (menu.style.display === 'block') {
                dropdown.classList.add('active');
            } else {
                dropdown.classList.remove('active');
            }
        }

        // Close dropdowns when clicking outside
        window.addEventListener('click', () => {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.style.display = 'none';
                menu.closest('.dropdown').classList.remove('active');
            });
        });

        // Modal functionality
        function openModal() {
            document.getElementById("supplierModal").style.display = "flex";
            document.getElementById("mainContent").classList.add("blurred");
            document.getElementById("modalTitle").textContent = "Add Supplier";
            document.getElementById("submitBtn").textContent = "Save Supplier";

            // Reset form for adding new supplier
            const form = document.getElementById("supplierForm");
            form.action = "{{ route('suppliers.store') }}";

            // Remove any existing method override
            form.querySelectorAll("input[name='_method']").forEach(el => el.remove());

            // Clear all form fields
            ['name', 'contact_person', 'phone', 'email', 'address'].forEach(id => {
                const field = document.getElementById(id);
                if (field) {
                    field.value = '';
                    // Trigger label animation check
                    field.dispatchEvent(new Event('input'));
                }
            });

            // Focus on first input
            setTimeout(() => {
                document.getElementById("name").focus();
            }, 100);
        }

        function closeModal() {
            document.getElementById("supplierModal").style.display = "none";
            document.getElementById("mainContent").classList.remove("blurred");
        }

        function editSupplier(supplier) {
            openModal();
            document.getElementById("modalTitle").textContent = "Edit Supplier";
            document.getElementById("submitBtn").textContent = "Update Supplier";

            // Set form action for updating
            const form = document.getElementById("supplierForm");
            form.action = `/dashboard/suppliers/${supplier.id}`;

            // Add method override for PUT request
            if (!form.querySelector('input[name="_method"]')) {
                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'PUT';
                form.appendChild(methodInput);
            }

            // Populate form fields
            const fields = {
                name: supplier.name || '',
                contact_person: supplier.contact_person || '',
                phone: supplier.phone || '',
                email: supplier.email || '',
                address: supplier.address || ''
            };

            Object.keys(fields).forEach(id => {
                const field = document.getElementById(id);
                if (field) {
                    field.value = fields[id];
                    // Force floating label to activate for filled fields
                    if (fields[id]) {
                        field.setAttribute('value', fields[id]);
                    }
                }
            });

            // Focus on name field
            setTimeout(() => {
                document.getElementById("name").focus();
                document.getElementById("name").select();
            }, 100);
        }

        // Enhanced floating label functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Handle all form inputs for floating labels
            const formInputs = document.querySelectorAll('#supplierForm input, #supplierForm textarea, #supplierForm select');

            formInputs.forEach(input => {
                // Check on page load for pre-filled values
                checkFloatingLabel(input);

                // Handle various events
                input.addEventListener('input', () => checkFloatingLabel(input));
                input.addEventListener('focus', () => checkFloatingLabel(input));
                input.addEventListener('blur', () => checkFloatingLabel(input));
                input.addEventListener('change', () => checkFloatingLabel(input));

                // Handle paste events
                input.addEventListener('paste', () => {
                    setTimeout(() => checkFloatingLabel(input), 10);
                });

                // Handle autofill detection
                if (input.tagName === 'INPUT') {
                    // Check for autofill periodically
                    const checkAutofill = () => {
                        if (input.matches(':-webkit-autofill') || input.value !== '') {
                            checkFloatingLabel(input);
                        }
                    };

                    // Check immediately and then periodically
                    setTimeout(checkAutofill, 100);
                    setTimeout(checkAutofill, 500);
                    setTimeout(checkAutofill, 1000);
                }
            });

            function checkFloatingLabel(input) {
                const hasValue = input.value && input.value.trim() !== '';
                const isFocused = document.activeElement === input;
                const isAutofilled = input.matches && input.matches(':-webkit-autofill');

                // Set data attribute to trigger CSS
                if (hasValue || isFocused || isAutofilled) {
                    input.setAttribute('data-filled', 'true');
                } else {
                    input.removeAttribute('data-filled');
                }
            }

            // Add additional CSS for better floating label behavior
            const style = document.createElement('style');
            style.textContent = `
                /* Enhanced floating label styles */
                #supplierForm input[data-filled="true"] + label,
                #supplierForm textarea[data-filled="true"] + label,
                #supplierForm select[data-filled="true"] + label {
                    top: 0 !important;
                    left: var(--spacing-md) !important;
                    transform: translateY(-50%) scale(0.85) !important;
                    font-size: var(--font-size-sm) !important;
                    color: var(--color-primary) !important;
                    background: var(--color-bg-primary) !important;
                    font-weight: var(--font-weight-semibold) !important;
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05) !important;
                    padding: 0 var(--spacing-sm) !important;
                    z-index: 3 !important;
                }

                /* Force immediate transition for autofill */
                #supplierForm input:-webkit-autofill + label {
                    transition: all 0.15s ease-out !important;
                }

                /* Ensure labels don't interfere with input clicks */
                #supplierForm label {
                    pointer-events: none !important;
                }

                /* Better visual separation */
                #supplierForm input:focus + label,
                #supplierForm textarea:focus + label {
                    color: var(--color-primary) !important;
                    font-weight: var(--font-weight-bold) !important;
                }
            `;
            document.head.appendChild(style);
        });

        // Keyboard shortcuts
        window.addEventListener("keydown", function(e) {
            if (e.key === "Escape") {
                closeModal();
            }

            // Add new supplier with Ctrl+N
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                openModal();
            }
        });

        // Auto-hide flash messages
        const flashMessage = document.getElementById('flashMessage');
        if (flashMessage) {
            setTimeout(() => {
                flashMessage.style.display = 'none';
            }, 5000);
        }

        // Form validation and submission
        document.getElementById('supplierForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');

            // Add loading state
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;

            // Re-enable after a delay (in case of validation errors)
            setTimeout(() => {
                submitBtn.classList.remove('loading');
                submitBtn.disabled = false;
            }, 3000);
        });

        // Enhanced table interactions
        document.querySelectorAll('.inventory-table tbody tr').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });

            row.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // Initialize animations on load
        document.addEventListener('DOMContentLoaded', function() {
            // Stagger table row animations
            const rows = document.querySelectorAll('.inventory-table tbody tr');
            rows.forEach((row, index) => {
                row.style.animationDelay = `${index * 0.05}s`;
                row.classList.add('fade-in');
            });
        });
    </script>

    @stack('scripts')
</body>

</html>
