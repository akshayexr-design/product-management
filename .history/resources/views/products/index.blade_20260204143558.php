<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h1>Product Management</h1>

        <!-- Add/Edit Form (Modal) -->
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#productModal" id="addBtn">Add Product</button>
        <div class="modal fade" id="productModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add Product</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="productForm" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" id="productId">
                            <div class="mb-3">
                                <label for="product_name">Name</label>
                                <input type="text" class="form-control" id="product_name" name="product_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="product_price">Price</label>
                                <input type="number" class="form-control" id="product_price" name="product_price" step="0.01" required>
                            </div>
                            <div class="mb-3">
                                <label for="product_description">Description</label>
                                <textarea class="form-control" id="product_description" name="product_description" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="images">Images (Multiple)</label>
                                <input type="file" class="form-control" id="images" name="images[]" multiple>
                            </div>
                            <div id="existingImages"></div>
                            <button type="submit" class="btn btn-primary">Save</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product List Table -->
        <table id="productsTable" class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Description</th>
                    <th>Images</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            let table = $('#productsTable').DataTable({
                ajax: '{{ route("products.list") }}',
                columns: [
                    { data: 'id' },
                    { data: 'product_name' },
                    { data: 'product_price' },
                    { data: 'product_description' },
                    { 
                        data: 'images',
                        render: function(data) {
                            return data.map(img => `<img src="/storage/${img.image_path}" width="50">`).join(' ');
                        }
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return `
                                <button class="btn btn-sm btn-warning editBtn" data-id="${row.id}">Edit</button>
                                <button class="btn btn-sm btn-danger deleteBtn" data-id="${row.id}">Delete</button>
                            `;
                        }
                    }
                ]
            });

            $('#productForm').submit(function(e) {
                e.preventDefault();
                
                let formData = new FormData(this);
                let url = $('#productId').val() 
                    ? '{{ url("/") }}/products/' + $('#productId').val() 
                    : '{{ route("products.store") }}';
                let method = $('#productId').val() ? 'PUT' : 'POST';

                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                $.ajax({
                    url: url,
                    type: method,
                    data: formData,
                    contentType: false,
                    processData: false,
                    headers: { 
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json' 
                    },
                    success: function(response) {
                        if (response.success) {
                            table.ajax.reload();
                            $('#productModal').modal('hide');
                            resetForm();
                            alert('Product saved successfully!'); 
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;

                            $.each(errors, function(field, messages) {
                                let input = $('#' + field);
                                input.addClass('is-invalid');
                                let feedback = input.next('.invalid-feedback');
                                if (feedback.length === 0) {
                                    feedback = $('<div class="invalid-feedback"></div>');
                                    input.after(feedback);
                                }
                                feedback.text(messages.join(' '));
                            });
                            if (xhr.responseJSON.message) {
                                alert(xhr.responseJSON.message);
                            }
                        } else {
                            console.error(xhr);
                            alert('Something went wrong. Check console.');
                        }
                    }
                });
            });

           // Edit Button
            $(document).on('click', '.editBtn', function() {
                let id = $(this).data('id');
                
                $.get('{{ url("/") }}/products/' + id + '/edit', function(data) {
                    $('#modalTitle').text('Edit Product');
                    $('#productId').val(data.id);
                    $('#product_name').val(data.product_name);
                    $('#product_price').val(data.product_price);
                    $('#product_description').val(data.product_description);

                    // Show existing images with delete buttons
                    let imagesHtml = '';
                    if (data.images && data.images.length > 0) {
                        imagesHtml = data.images.map(img => `
                            <div class="position-relative d-inline-block me-2 mb-2">
                                <img src="/storage/${img.image_path}" width="80" class="img-thumbnail">
                                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 delete-image-btn" 
                                        data-image-id="${img.id}" title="Delete this image">
                                    ×
                                </button>
                            </div>
                        `).join('');
                    } else {
                        imagesHtml = '<p class="text-muted">No images yet</p>';
                    }

                    $('#existingImages').html(imagesHtml);
                    $('#productModal').modal('show');
                }).fail(function() {
                    alert('Failed to load product data');
                });
            });

            // Delete Button
            $(document).on('click', '.deleteBtn', function() {
                let id = $(this).data('id');
                if (confirm('Are you sure?')) {
                    $.ajax({
                        url: '{{ url("/") }}/products/' + id,
                        type: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        success: function(response) {
                            if (response.success) {
                                table.ajax.reload();
                            }
                        }
                    });
                }
            });

            // Delete individual image
$(document).on('click', '.delete-image-btn', function() {
    let imageId = $(this).data('image-id');
    let btn = $(this); // for removing from DOM

    if (confirm('Delete this image?')) {
        $.ajax({
            url: '{{ url("/") }}/product-images/' + imageId,
            type: 'DELETE',
            headers: { 
                'X-CSRF-TOKEN': '{{ csrf_token() }}' 
            },
            success: function(response) {
                if (response.success) {
                    // Remove the image container from modal
                    btn.closest('.position-relative').remove();

                    // Optional: reload table to reflect change (if you show image count or something)
                    table.ajax.reload(null, false); // false = keep current page/pagination

                    // Optional: if no images left, show message
                    if ($('#existingImages .position-relative').length === 0) {
                        $('#existingImages').html('<p class="text-muted">No images yet</p>');
                    }
                }
            },
            error: function(xhr) {
                console.error(xhr);
                alert('Failed to delete image');
            }
        });
    }
});

            // Add Button
            $('#addBtn').click(function() {
                resetForm();
                $('#modalTitle').text('Add Product');
                $('#productModal').modal('show');
            });

            function resetForm() {
                $('#productForm')[0].reset();
                $('#productId').val('');
                $('#existingImages').html('');
            }
        });
    </script>
</body>
</html>