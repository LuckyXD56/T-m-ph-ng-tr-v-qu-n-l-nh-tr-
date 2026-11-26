(function () {
    'use strict';

    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const baseMeta = document.querySelector('meta[name="base-path"]');
    const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') || '' : '';
    const basePath = baseMeta ? baseMeta.getAttribute('content') || '' : '';

    function appUrl(path) {
        const clean = String(path || '').replace(/^\/+/, '');
        return (basePath ? basePath : '') + '/' + clean;
    }

    async function apiFetch(endpoint, options = {}) {
        const opts = Object.assign({ method: 'POST', headers: {} }, options);
        opts.headers = Object.assign({}, opts.headers, {
            'X-CSRF-Token': csrfToken
        });
        if (!(opts.body instanceof FormData) && !opts.headers['Content-Type']) {
            opts.headers['Content-Type'] = 'application/json';
        }
        const res = await fetch(appUrl(endpoint), opts);
        let data;
        try {
            data = await res.json();
        } catch (err) {
            throw new Error('Phản hồi từ máy chủ không hợp lệ (' + res.status + ')');
        }
        return data;
    }

    function showAlert(el, message, isSuccess) {
        if (!el) return;
        el.textContent = message || '';
        el.className = 'alert show ' + (isSuccess ? 'alert-success' : 'alert-error');
    }

    function formatVnd(amount) {
        return Math.round(Number(amount || 0)).toLocaleString('vi-VN') + ' đ';
    }

    // 1. Print button handler
    document.querySelectorAll('.js-print-btn').forEach((btn) => {
        btn.addEventListener('click', () => window.print());
    });

    // 2. Room detail thumbnail gallery
    const mainImage = document.getElementById('mainImage');
    document.querySelectorAll('.js-thumb-img').forEach((img) => {
        img.addEventListener('click', () => {
            if (mainImage) {
                mainImage.src = img.src;
            }
        });
    });

    // 3. Tab switcher
    document.querySelectorAll('.js-tab-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            const target = btn.getAttribute('data-tab');
            const group = btn.closest('.js-tab-container') || document;
            group.querySelectorAll('.js-tab-btn').forEach((b) => b.classList.remove('active'));
            group.querySelectorAll('.tab-panel').forEach((p) => p.classList.remove('active'));
            btn.classList.add('active');
            const panel = document.getElementById(target);
            if (panel) panel.classList.add('active');
        });
    });

    // 4. Generic modals
    function closeModal(modal) {
        if (modal) modal.classList.remove('open');
    }
    document.querySelectorAll('.js-modal-close').forEach((btn) => {
        btn.addEventListener('click', () => closeModal(btn.closest('.modal-backdrop')));
    });
    document.querySelectorAll('.modal-backdrop').forEach((backdrop) => {
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) closeModal(backdrop);
        });
    });

    // 5. Auth: Login & Register
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const msgEl = document.getElementById('loginMessage');
            try {
                const data = await apiFetch('auth/login.php', {
                    body: JSON.stringify({
                        phone: document.getElementById('phone').value.trim(),
                        password: document.getElementById('password').value
                    })
                });
                if (data.status === 'success') {
                    window.location.href = data.redirect;
                } else {
                    showAlert(msgEl, data.message, false);
                }
            } catch (err) {
                showAlert(msgEl, err.message, false);
            }
        });
    }

    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const msgEl = document.getElementById('registerMessage');
            try {
                const data = await apiFetch('auth/register.php', {
                    body: JSON.stringify({
                        role: document.getElementById('role').value,
                        fullname: document.getElementById('fullname').value.trim(),
                        phone: document.getElementById('phone').value.trim(),
                        password: document.getElementById('password').value
                    })
                });
                showAlert(msgEl, data.message, data.status === 'success');
                if (data.status === 'success') {
                    setTimeout(() => { window.location.href = appUrl('login.php'); }, 1100);
                }
            } catch (err) {
                showAlert(msgEl, err.message, false);
            }
        });
    }

    // 6. Student: Book appointment
    const bookForm = document.getElementById('bookAppointmentForm');
    if (bookForm) {
        bookForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const msgEl = document.getElementById('msgBook');
            try {
                const data = await apiFetch('sinhvien/api/book_appointment.php', {
                    body: JSON.stringify({
                        room_id: bookForm.getAttribute('data-room-id'),
                        date: document.getElementById('bookDate').value,
                        time: document.getElementById('bookTime').value,
                        note: document.getElementById('bookNote').value.trim()
                    })
                });
                showAlert(msgEl, data.message, data.status === 'success');
                if (data.status === 'success') {
                    bookForm.reset();
                }
            } catch (err) {
                showAlert(msgEl, err.message, false);
            }
        });
    }

    // Student: Cancel own appointment
    document.querySelectorAll('.js-cancel-appointment').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (!confirm('Bạn chắc chắn muốn hủy lịch hẹn này?')) return;
            const id = btn.getAttribute('data-id');
            try {
                const data = await apiFetch('sinhvien/api/cancel_appointment.php', {
                    body: JSON.stringify({ id })
                });
                if (data.status === 'success') {
                    location.reload();
                } else {
                    alert(data.message);
                }
            } catch (err) {
                alert(err.message);
            }
        });
    });

    // Student: Notify payment for invoice
    document.querySelectorAll('.js-pay-invoice').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (!confirm('Xác nhận bạn đã thanh toán hóa đơn này và gửi yêu cầu duyệt tới Quản lý?')) return;
            const id = btn.getAttribute('data-id');
            try {
                const data = await apiFetch('sinhvien/api/pay_invoice.php', {
                    body: JSON.stringify({ id })
                });
                if (data.status === 'success') {
                    location.reload();
                } else {
                    alert(data.message);
                }
            } catch (err) {
                alert(err.message);
            }
        });
    });

    // 7. Landlord: Create manager, create motel, assign manager
    const createManagerForm = document.getElementById('createManagerForm');
    if (createManagerForm) {
        createManagerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const msgEl = document.getElementById('managerMsg');
            try {
                const data = await apiFetch('landlord/api/create_manager.php', {
                    body: JSON.stringify({
                        fullname: document.getElementById('mName').value.trim(),
                        phone: document.getElementById('mPhone').value.trim(),
                        password: document.getElementById('mPass').value
                    })
                });
                showAlert(msgEl, data.message, data.status === 'success');
                if (data.status === 'success') setTimeout(() => location.reload(), 900);
            } catch (err) {
                showAlert(msgEl, err.message, false);
            }
        });
    }

    const landlordMotelForm = document.getElementById('landlordCreateMotelForm');
    if (landlordMotelForm) {
        landlordMotelForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const msgEl = document.getElementById('motelMsg');
            try {
                const data = await apiFetch('landlord/api/save_motel.php', {
                    body: JSON.stringify({
                        name: document.getElementById('lmName').value.trim(),
                        address: document.getElementById('lmAddress').value.trim(),
                        district: document.getElementById('lmDistrict').value,
                        manager_id: document.getElementById('lmManager').value,
                        description: document.getElementById('lmDesc').value.trim()
                    })
                });
                showAlert(msgEl, data.message, data.status === 'success');
                if (data.status === 'success') setTimeout(() => location.reload(), 900);
            } catch (err) {
                showAlert(msgEl, err.message, false);
            }
        });
    }

    document.querySelectorAll('.js-assign-manager-btn').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const motelId = btn.getAttribute('data-motel-id');
            const select = document.getElementById('assign-mgr-' + motelId);
            if (!select) return;
            try {
                const data = await apiFetch('landlord/api/assign_manager.php', {
                    body: JSON.stringify({
                        motel_id: motelId,
                        manager_id: select.value
                    })
                });
                alert(data.message);
                if (data.status === 'success') location.reload();
            } catch (err) {
                alert(err.message);
            }
        });
    });

    // 8. Manager: Add motel
    const addMotelForm = document.getElementById('addMotelForm');
    if (addMotelForm) {
        addMotelForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const msgEl = document.getElementById('motelMsg');
            try {
                const data = await apiFetch('manager/api/add_motel.php', {
                    body: JSON.stringify({
                        name: document.getElementById('mName').value.trim(),
                        address: document.getElementById('mAddress').value.trim(),
                        district: document.getElementById('mDistrict').value,
                        description: document.getElementById('mDesc').value.trim()
                    })
                });
                showAlert(msgEl, data.message, data.status === 'success');
                if (data.status === 'success') setTimeout(() => location.reload(), 900);
            } catch (err) {
                showAlert(msgEl, err.message, false);
            }
        });
    }

    // Manager: Add room
    const addRoomForm = document.getElementById('addRoomForm');
    if (addRoomForm) {
        addRoomForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const msgEl = document.getElementById('roomMsg');
            const formData = new FormData(addRoomForm);
            formData.set('has_ac', document.getElementById('rAc').checked ? '1' : '0');
            formData.set('has_water_heater', document.getElementById('rWaterHeater').checked ? '1' : '0');
            formData.set('has_parking', document.getElementById('rParking').checked ? '1' : '0');
            try {
                const data = await apiFetch('manager/api/add_room.php', { body: formData });
                showAlert(msgEl, data.message, data.status === 'success');
                if (data.status === 'success') setTimeout(() => location.reload(), 900);
            } catch (err) {
                showAlert(msgEl, err.message, false);
            }
        });
    }

    // Manager: Edit room modal
    const editRoomModal = document.getElementById('editRoomModal');
    document.querySelectorAll('.js-open-edit-room').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!editRoomModal) return;
            document.getElementById('erId').value = btn.getAttribute('data-id');
            document.getElementById('erName').value = btn.getAttribute('data-name');
            document.getElementById('erPrice').value = btn.getAttribute('data-price');
            document.getElementById('erArea').value = btn.getAttribute('data-area');
            document.getElementById('erElectric').value = btn.getAttribute('data-electric');
            document.getElementById('erWater').value = btn.getAttribute('data-water');
            document.getElementById('erWaterType').value = btn.getAttribute('data-water-type');
            document.getElementById('erService').value = btn.getAttribute('data-service');
            document.getElementById('erStatus').value = btn.getAttribute('data-status');
            document.getElementById('erDesc').value = btn.getAttribute('data-desc') || '';
            document.getElementById('erAc').checked = btn.getAttribute('data-ac') === '1';
            document.getElementById('erWaterHeater').checked = btn.getAttribute('data-wh') === '1';
            document.getElementById('erParking').checked = btn.getAttribute('data-parking') === '1';
            editRoomModal.classList.add('open');
        });
    });

    const editRoomForm = document.getElementById('editRoomForm');
    if (editRoomForm) {
        editRoomForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                const data = await apiFetch('manager/api/update_room.php', {
                    body: JSON.stringify({
                        room_id: document.getElementById('erId').value,
                        room_name: document.getElementById('erName').value.trim(),
                        price: document.getElementById('erPrice').value,
                        area: document.getElementById('erArea').value,
                        electric_price: document.getElementById('erElectric').value,
                        water_price: document.getElementById('erWater').value,
                        water_calc_type: document.getElementById('erWaterType').value,
                        service_fee: document.getElementById('erService').value,
                        status: document.getElementById('erStatus').value,
                        description: document.getElementById('erDesc').value.trim(),
                        has_ac: document.getElementById('erAc').checked,
                        has_water_heater: document.getElementById('erWaterHeater').checked,
                        has_parking: document.getElementById('erParking').checked
                    })
                });
                alert(data.message);
                if (data.status === 'success') location.reload();
            } catch (err) {
                alert(err.message);
            }
        });
    }

    document.querySelectorAll('.js-delete-room').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (!confirm('Bạn chắc chắn muốn xóa phòng này?')) return;
            try {
                const data = await apiFetch('manager/api/delete_room.php', {
                    body: JSON.stringify({ room_id: btn.getAttribute('data-id') })
                });
                alert(data.message);
                if (data.status === 'success') location.reload();
            } catch (err) {
                alert(err.message);
            }
        });
    });

    // Manager: Create contract modal
    const contractModal = document.getElementById('contractModal');
    document.querySelectorAll('.js-open-contract-modal').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!contractModal) return;
            document.getElementById('contractModalTitle').textContent = 'Tạo hợp đồng — ' + btn.getAttribute('data-room-name');
            document.getElementById('cRoomId').value = btn.getAttribute('data-room-id');
            document.getElementById('cStartDate').value = new Date().toISOString().split('T')[0];
            document.getElementById('cDeposit').value = btn.getAttribute('data-price') || 1000000;
            document.getElementById('cPhone').value = '';
            contractModal.classList.add('open');
        });
    });

    const createContractForm = document.getElementById('createContractForm');
    if (createContractForm) {
        createContractForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            try {
                const data = await apiFetch('manager/api/create_contract.php', {
                    body: JSON.stringify({
                        room_id: document.getElementById('cRoomId').value,
                        student_phone: document.getElementById('cPhone').value.trim(),
                        student_cccd: document.getElementById('cCccd').value.trim(),
                        start_date: document.getElementById('cStartDate').value,
                        months: document.getElementById('cMonths').value,
                        deposit: document.getElementById('cDeposit').value,
                        num_occupants: document.getElementById('cOccupants').value,
                        initial_electric_index: document.getElementById('cElecInit').value,
                        initial_water_index: document.getElementById('cWaterInit').value,
                        note: document.getElementById('cNote').value.trim()
                    })
                });
                alert(data.message);
                if (data.status === 'success') location.reload();
            } catch (err) {
                alert(err.message);
            }
        });
    }

    document.querySelectorAll('.js-end-contract').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (!confirm('Xác nhận thanh lý / kết thúc hợp đồng và trả phòng về trạng thái Trống?')) return;
            try {
                const data = await apiFetch('manager/api/end_contract.php', {
                    body: JSON.stringify({ contract_id: btn.getAttribute('data-contract-id') })
                });
                alert(data.message);
                if (data.status === 'success') location.reload();
            } catch (err) {
                alert(err.message);
            }
        });
    });

    // Manager: Appointments
    document.querySelectorAll('.js-appointment-action').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const status = btn.getAttribute('data-status');
            const label = status === 'da_duyet' ? 'duyệt' : (status === 'hoan_thanh' ? 'hoàn thành' : 'hủy');
            if (!confirm('Bạn chắc chắn muốn ' + label + ' lịch hẹn này?')) return;
            try {
                const data = await apiFetch('manager/api/update_appointment.php', {
                    body: JSON.stringify({
                        id: btn.getAttribute('data-id'),
                        status: status
                    })
                });
                if (data.status === 'success') {
                    location.reload();
                } else {
                    alert(data.message);
                }
            } catch (err) {
                alert(err.message);
            }
        });
    });

    // 9. Manager/Landlord: Monthly Utility Invoices
    function recalcInvoiceRow(row) {
        if (!row) return;
        const roomPrice = Number(row.getAttribute('data-room-price') || 0);
        const elecPrice = Number(row.getAttribute('data-elec-price') || 0);
        const waterPrice = Number(row.getAttribute('data-water-price') || 0);
        const waterType = row.getAttribute('data-water-type') || 'theo_khoi';
        const occupants = Number(row.getAttribute('data-occupants') || 1);

        const elecOldEl = row.querySelector('.js-elec-old');
        const elecNewEl = row.querySelector('.js-elec-new');
        const waterOldEl = row.querySelector('.js-water-old');
        const waterNewEl = row.querySelector('.js-water-new');
        const serviceEl = row.querySelector('.js-service-fee');
        const otherEl = row.querySelector('.js-other-fee');

        const elecOld = Number(elecOldEl ? elecOldEl.value : 0);
        const elecNew = Number(elecNewEl ? elecNewEl.value : 0);
        const elecUsage = Math.max(0, elecNew - elecOld);
        const elecTotal = elecUsage * elecPrice;

        let waterTotal = 0;
        let waterUsage = 0;
        if (waterType === 'theo_nguoi') {
            waterUsage = occupants;
            waterTotal = occupants * waterPrice;
        } else {
            const wOld = Number(waterOldEl ? waterOldEl.value : 0);
            const wNew = Number(waterNewEl ? waterNewEl.value : 0);
            waterUsage = Math.max(0, wNew - wOld);
            waterTotal = waterUsage * waterPrice;
        }

        const serviceFee = Math.max(0, Number(serviceEl ? serviceEl.value : 0));
        const otherFee = Math.max(0, Number(otherEl ? otherEl.value : 0));
        const total = roomPrice + elecTotal + waterTotal + serviceFee + otherFee;

        const elecUsageOut = row.querySelector('.js-elec-usage');
        const waterUsageOut = row.querySelector('.js-water-usage');
        const totalOut = row.querySelector('.js-row-total');
        if (elecUsageOut) elecUsageOut.textContent = elecUsage + ' kWh (' + formatVnd(elecTotal) + ')';
        if (waterUsageOut) {
            waterUsageOut.textContent = waterType === 'theo_nguoi'
                ? occupants + ' người (' + formatVnd(waterTotal) + ')'
                : waterUsage + ' m³ (' + formatVnd(waterTotal) + ')';
        }
        if (totalOut) totalOut.textContent = formatVnd(total);
    }

    document.querySelectorAll('.js-invoice-row').forEach((row) => {
        recalcInvoiceRow(row);
        row.querySelectorAll('input').forEach((inp) => {
            inp.addEventListener('input', () => recalcInvoiceRow(row));
        });
    });

    document.querySelectorAll('.js-save-invoice').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const row = btn.closest('.js-invoice-row');
            if (!row) return;
            const payload = {
                contract_id: row.getAttribute('data-contract-id'),
                billing_month: row.getAttribute('data-month'),
                billing_year: row.getAttribute('data-year'),
                electric_old: row.querySelector('.js-elec-old').value,
                electric_new: row.querySelector('.js-elec-new').value,
                water_old: row.querySelector('.js-water-old') ? row.querySelector('.js-water-old').value : 0,
                water_new: row.querySelector('.js-water-new') ? row.querySelector('.js-water-new').value : 0,
                service_fee: row.querySelector('.js-service-fee').value,
                other_fee: row.querySelector('.js-other-fee').value,
                note: row.querySelector('.js-invoice-note').value.trim()
            };
            try {
                const data = await apiFetch('manager/api/save_invoice.php', {
                    body: JSON.stringify(payload)
                });
                alert(data.message);
                if (data.status === 'success') location.reload();
            } catch (err) {
                alert(err.message);
            }
        });
    });

    document.querySelectorAll('.js-mark-invoice-paid').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (!confirm('Xác nhận đã thu đủ tiền hóa đơn này?')) return;
            try {
                const data = await apiFetch('manager/api/update_invoice_status.php', {
                    body: JSON.stringify({
                        invoice_id: btn.getAttribute('data-id'),
                        status: 'da_thanh_toan'
                    })
                });
                alert(data.message);
                if (data.status === 'success') location.reload();
            } catch (err) {
                alert(err.message);
            }
        });
    });

    document.querySelectorAll('.js-delete-invoice').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (!confirm('Xóa hóa đơn chưa thanh toán này?')) return;
            try {
                const data = await apiFetch('manager/api/delete_invoice.php', {
                    body: JSON.stringify({ invoice_id: btn.getAttribute('data-id') })
                });
                alert(data.message);
                if (data.status === 'success') location.reload();
            } catch (err) {
                alert(err.message);
            }
        });
    });

    // 10. Admin & Landlord: Toggle user active
    document.querySelectorAll('.js-toggle-user').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const endpoint = btn.getAttribute('data-endpoint') || 'admin/api/toggle_user.php';
            try {
                const data = await apiFetch(endpoint, {
                    body: JSON.stringify({ user_id: btn.getAttribute('data-user-id') })
                });
                alert(data.message);
                if (data.status === 'success') location.reload();
            } catch (err) {
                alert(err.message);
            }
        });
    });
})();
