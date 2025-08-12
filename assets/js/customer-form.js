jQuery(document).ready(function ($) {
    let phoneIndex = $("#phone-numbers-container .phone-input-wrapper").length;
    if (phoneIndex === 0) {
        // If no phone fields are present (e.g., new form), initialize with one
        addPhoneField(0);
    }

    // Add Phone Field
    $("#add-phone-field").on("click", function () {
        addPhoneField(phoneIndex);
        phoneIndex++;
    });

    // Remove Phone Field
    $("#phone-numbers-container").on("click", ".remove-phone-field", function () {
        if ($("#phone-numbers-container .phone-input-wrapper").length > 1) {
            $(this).closest(".phone-input-wrapper").remove();
        } else {
            // Optionally, clear the fields instead of removing if only one is left
            $(this).closest(".phone-input-wrapper").find('input[type="text"]').val("");
            $(this).closest(".phone-input-wrapper").find('input[type="checkbox"]').prop("checked", false);
            $(this).closest(".phone-input-wrapper").find('input[type="hidden"]').val(""); // Clear hidden ID for existing phones
        }
    });

    function addPhoneField(index) {
        const newPhoneField = `
            <div class="phone-input-wrapper">
                <div class="input-group mb-2">
                    <input type="hidden" name="phone_numbers[${index}][id]" value="0"> <!-- New field, ID 0 -->
                    <input type="text" class="form-control aerp-phone-input" name="phone_numbers[${index}][number]" placeholder="Số điện thoại">
                    <div class="input-group-text">
                        <input type="checkbox" name="phone_numbers[${index}][primary]" value="1"> &nbsp; Chính
                    </div>
                    <input type="text" class="form-control" name="phone_numbers[${index}][note]" placeholder="Ghi chú">
                    <button type="button" class="btn btn-outline-danger remove-phone-field">Xóa</button>
                </div>
            </div>
        `;
        $("#phone-numbers-container").append(newPhoneField);
    }

    // Handle delete attachment (for form-edit.php)
    $("#existing-attachments-container").on("click", ".delete-attachment", function () {
        const attachmentId = $(this).data("attachment-id");
        const $attachmentDiv = $(this).closest(".d-flex");

        if (confirm("Bạn có chắc chắn muốn xóa file đính kèm này không?")) {
            $.ajax({
                url: aerp_crm_ajax.ajaxurl, // WordPress global AJAX URL
                type: "POST",
                data: {
                    action: "aerp_delete_customer_attachment", // Our custom AJAX action
                    attachment_id: attachmentId,
                    _wpnonce: aerp_crm_ajax._wpnonce_delete_attachment, // Get nonce from localized script
                },
                success: function (response) {
                    if (response.success) {
                        $attachmentDiv.remove(); // Visually remove on success
                        alert(response.data); // Show success message
                    } else {
                        alert("Lỗi: " + response.data); // Show error message
                    }
                },
                error: function (xhr, status, error) {
                    console.error("AJAX Error: ", status, error);
                    alert("Đã xảy ra lỗi khi xóa file.");
                },
            });
        }
    });

    // Normalize and real-time duplicate check
    function normalizePhone(value) {
        return (value || "").replace(/\D+/g, "");
    }

    // Validate Vietnamese mobile phone number
    function isValidVNPhone(value) {
        if (!value) return false;
        // Convert 84xxxxxxxxx to 0xxxxxxxxx if needed
        if (value.startsWith("84") && value.length === 11) {
            value = "0" + value.substring(2);
        }
        // VN mobile regex: 0(3[2-9]|5[689]|7[06-9]|8(1-5|8|9)|9(0-4|6-9)) + 7 digits
        const re = /^0(?:3[2-9]|5[689]|7[06-9]|8(?:[1-5]|8|9)|9(?:[0-4]|[6-9]))\d{7}$/;
        return re.test(value);
    }

    function showPhoneStatus($input, status) {
        // Remove old status
        $input.removeClass("is-invalid is-valid");
        $input.closest(".phone-input-wrapper").find(".invalid-feedback, .valid-feedback").remove();

        if (status === "empty") {
            return;
        }
        if (status === "invalid") {
            $input.addClass("is-invalid");
            $input.closest(".phone-input-wrapper").append('<div class="invalid-feedback">Số điện thoại không hợp lệ.</div>');
        } else if (status === "duplicate") {
            $input.addClass("is-invalid");
            $input.closest(".phone-input-wrapper").append('<div class="invalid-feedback">Số điện thoại đã tồn tại.</div>');
        } else if (status === "ok") {
            $input.addClass("is-valid");
            $input.closest(".phone-input-wrapper").append('<div class="valid-feedback">Số hợp lệ.</div>');
        }
    }

    // Local duplicate within the form
    function hasLocalDuplicate(value, $current) {
        let count = 0;
        $(".aerp-phone-input").each(function () {
            const v = normalizePhone($(this).val());
            if (v === value && v !== "") {
                count++;
            }
        });
        // If more than 1 occurrence, it's duplicate
        return count > 1;
    }

    // Debounced remote check
    let phoneCheckTimer = null;
    $(document).on("input blur", ".aerp-phone-input", function (event) {
        const $input = $(this);
        const raw = $input.val();
        const normalized = normalizePhone(raw);
        // Write back normalized value (keep digits only) for UX clarity on blur
        if (event.type === "blur") {
            $input.val(normalized);
        }

        if (normalized === "") {
            showPhoneStatus($input, "empty");
            return;
        }

        // Validate Vietnam phone pattern first
        if (!isValidVNPhone(normalized)) {
            showPhoneStatus($input, "invalid");
            return;
        }

        // Local duplicate check
        if (hasLocalDuplicate(normalized, $input)) {
            showPhoneStatus($input, "duplicate");
            return;
        }

        clearTimeout(phoneCheckTimer);
        phoneCheckTimer = setTimeout(function () {
            const excludeCustomerId = $("input[name='customer_id']").val() || 0;
            $.post(
                aerp_crm_ajax.ajaxurl,
                {
                    action: "aerp_crm_check_phone_unique",
                    phone: normalized,
                    exclude_customer_id: excludeCustomerId,
                    _wpnonce: aerp_crm_ajax._wpnonce_check_phone,
                },
                function (resp) {
                    if (resp && resp.success) {
                        if (resp.data && resp.data.exists) {
                            showPhoneStatus($input, "duplicate");
                        } else {
                            showPhoneStatus($input, "ok");
                        }
                    } else {
                        // On error, do not block but clear status
                        showPhoneStatus($input, "empty");
                    }
                }
            ).fail(function () {
                showPhoneStatus($input, "empty");
            });
        }, 250);
    });

    // Prevent form submit if no valid phone number
    $("form.aerp-customer-form").on("submit", function (e) {
        let validCount = 0;
        $(".aerp-phone-input").each(function () {
            const normalized = normalizePhone($(this).val());
            if (isValidVNPhone(normalized)) {
                validCount++;
            }
        });
        if (validCount === 0) {
            alert("Bạn phải nhập ít nhất 1 số điện thoại hợp lệ.");
            e.preventDefault();
            return false;
        }
    });

    $(document).on("click", ".copy-phone", function (e) {
        e.preventDefault();
        const phone = $(this).data("phone");
        if (navigator.clipboard) {
            navigator.clipboard.writeText(phone).then(() => {
                const $icon = $(this).find("i");
                const original = $icon.attr("data-original-class") || $icon.attr("class");
                if (!$icon.attr("data-original-class")) {
                    $icon.attr("data-original-class", original);
                }
                $icon.removeClass().addClass("fas fa-check text-success");
                setTimeout(() => {
                    $icon.removeClass().addClass($icon.attr("data-original-class"));
                }, 1200);
            });
        }
    });
});
