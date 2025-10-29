// --- MAIN DOMContentLoaded ---
document.addEventListener('DOMContentLoaded', () => {

    // DOM elements
    const tableSelect = document.getElementById('tableSelect');
    const membersTableBody = document.getElementById('membersTableBody');
    const voterTableBody = document.querySelector('#voterStatusTable tbody');
    const selectAll = document.getElementById('selectAll');
    const sortSelect = document.getElementById('sortSelect');
    const btnSort = document.getElementById('btnSort');
    const bulkEligible = document.getElementById('bulkEligible');
    const bulkIneligible = document.getElementById('bulkIneligible');
    const bulkVerify = document.getElementById('bulkVerify');
    const searchInput = document.getElementById('searchInput');
    const modalSubmit = document.getElementById('modalSubmit');
    const reviewModalEl = document.getElementById('reviewModal');
    const reviewModal = new bootstrap.Modal(reviewModalEl);

    // Summary bar
    const eligibleCountEl = document.getElementById('eligibleCount');
    const ineligibleCountEl = document.getElementById('ineligibleCount');
    const pendingCountEl = document.getElementById('pendingCount');

    let currentData = { members: [], voters: [] };
    let currentTable = tableSelect ? tableSelect.value : 'zsmMEMBERS';
    let currentReviewID = null;

    // --- FETCH DATA ---
    function fetchData(selectedTable = "registeredVOTERS") {
        // Fetch Members
        fetch('a3b9f1d7e6c2x4z8k0q5r7p1s.php?action=fetchMembers&table=zsmMEMBERS')
            .then(res => res.json())
            .then(members => {
                currentData.members = members;
                renderMembersTable(members);
            })
            .catch(err => console.error('Error fetching members:', err));

        // Fetch Voter/Eligibility
        fetch(`a3b9f1d7e6c2x4z8k0q5r7p1s.php?action=fetch_voter_status&table=${selectedTable}`)
            .then(res => res.json())
            .then(voters => {
                currentData.voters = voters;
                renderVoterTable(voters);

                if (Array.isArray(voters) && selectedTable === "voterSTATUS") {
                    updateSummary(voters);
                }
            })
            .catch(err => console.error('Error fetching voters:', err));
    }

    // --- UPDATE SUMMARY COUNTS ---
    function updateSummary(voters) {
        const eligibleCount = voters.filter(v => v.Status === 'Eligible').length;
        const ineligibleCount = voters.filter(v => v.Status === 'Ineligible').length;
        const pendingCount = voters.filter(v => v.Status === 'Pending').length;

        if (eligibleCountEl) eligibleCountEl.innerText = eligibleCount;
        if (ineligibleCountEl) ineligibleCountEl.innerText = ineligibleCount;
        if (pendingCountEl) pendingCountEl.innerText = pendingCount;
    }

    // --- RENDER MEMBERS TABLE ---
    function renderMembersTable(data) {
        if (!membersTableBody) return;
        membersTableBody.innerHTML = '';

        data.forEach(row => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${row.zsmID}</td>
                <td>${row.Firstname}</td>
                <td>${row.Surname}</td>
                <td>${row.Membership}</td>
                <td>${row.City}</td>
                <td>${row.EDU_Status}</td>
            `;
            membersTableBody.appendChild(tr);
        });
    }

    // --- RENDER VOTER TABLE ---
    function renderVoterTable(data) {
        if (!Array.isArray(data)) return;
        voterTableBody.innerHTML = '';

        const searchValue = searchInput ? searchInput.value.toLowerCase() : '';
        let eligibleCount = 0, ineligibleCount = 0, pendingCount = 0;

        data.forEach(row => {
            if (searchValue && !(
                (row.Firstname && row.Firstname.toLowerCase().includes(searchValue)) ||
                (row.Surname && row.Surname.toLowerCase().includes(searchValue)) ||
                (row.City && row.City.toLowerCase().includes(searchValue))
            )) return;

            let statusClass = '';
            switch(row.Status){
                case 'Eligible': statusClass = 'eligible'; eligibleCount++; break;
                case 'Ineligible': statusClass = 'ineligible'; ineligibleCount++; break;
                case 'Pending': statusClass = 'pending'; pendingCount++; break;
            }

            const tr = document.createElement('tr');
            if(statusClass) tr.classList.add(statusClass);
            tr.innerHTML = `
                <td><input type="checkbox" class="rowCheckbox" data-id="${row.voterID}"></td>
                <td>${row.voterID}</td>
                <td>${row.Firstname}</td>
                <td>${row.Surname}</td>
                <td>${row.City}</td>
                <td>${row.AcademicYear || ''}</td>
                <td>${row.Status || 'Pending'}</td>
                <td>${row.Reason || ''}</td>
            `;
            voterTableBody.appendChild(tr);
        });

        if (eligibleCountEl) eligibleCountEl.textContent = eligibleCount;
        if (ineligibleCountEl) ineligibleCountEl.textContent = ineligibleCount;
        if (pendingCountEl) pendingCountEl.textContent = pendingCount;

        attachVoterRowEvents();
    }

    // --- VOTER ROW EVENTS ---
    function attachVoterRowEvents() {
        document.querySelectorAll('.rowCheckbox').forEach(cb => {
            cb.addEventListener('change', () => {
                if(selectAll) {
                    selectAll.checked = document.querySelectorAll('.rowCheckbox:checked').length === document.querySelectorAll('.rowCheckbox').length;
                }
            });
        });
    }

    // --- BULK UPDATE ---
    function bulkUpdate(status) {
        const selected = Array.from(document.querySelectorAll('.rowCheckbox:checked')).map(cb => cb.dataset.id);
        if (selected.length === 0) { alert('No voters selected!'); return; }

        fetch('a3b9f1d7e6c2x4z8k0q5r7p1s.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: `action=bulkUpdate&status=${status}&ids[]=${selected.join('&ids[]=')}`
        }).then(() => fetchData(currentTable));
    }

    // --- SELECT ALL ---
    if (selectAll) {
        selectAll.addEventListener('change', () => {
            document.querySelectorAll('.rowCheckbox').forEach(cb => cb.checked = selectAll.checked);
        });
    }

    // --- SEARCH BOTH TABLES ---
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.toLowerCase();
            const filteredMembers = currentData.members.filter(row =>
                !query || (
                    (row.Firstname && row.Firstname.toLowerCase().includes(query)) ||
                    (row.Surname && row.Surname.toLowerCase().includes(query)) ||
                    (row.City && row.City.toLowerCase().includes(query))
                )
            );
            renderMembersTable(filteredMembers);

            const filteredVoters = currentData.voters.filter(row =>
                !query || (
                    (row.Firstname && row.Firstname.toLowerCase().includes(query)) ||
                    (row.Surname && row.Surname.toLowerCase().includes(query)) ||
                    (row.City && row.City.toLowerCase().includes(query))
                )
            );
            renderVoterTable(filteredVoters);
        });
    }

// --- SORT BOTH TABLES ---
if (btnSort && sortSelect) {
    btnSort.addEventListener('click', () => {
        const field = sortSelect.value;
        if(!field) return;

        // Sort Members table
        const sortedMembers = [...currentData.members].sort((a,b) =>
            (a[field] && b[field]) ? a[field].toString().localeCompare(b[field].toString()) : 0
        );
        renderMembersTable(sortedMembers);

        // Sort Voter table
        const sortedVoters = [...currentData.voters].sort((a,b) =>
            (a[field] && b[field]) ? a[field].toString().localeCompare(b[field].toString()) : 0
        );
        renderVoterTable(sortedVoters);
    });
}


    // --- CSV Upload Handling ---
    const csvForm = document.getElementById("csvForm");
    const csvFile = document.getElementById("csvFile");
    const uploadResult = document.getElementById("uploadResult");

    if (csvForm) {
        csvForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            console.log("📤 CSV form submitted");

            if (!csvFile.files.length) {
                uploadResult.textContent = "⚠️ Please select a CSV file first.";
                uploadResult.style.color = "red";
                return;
            }

            const formData = new FormData();
            formData.append("action", "uploadCSV");
            formData.append("csvFile", csvFile.files[0]);

            try {
                const response = await fetch("a3b9f1d7e6c2x4z8k0q5r7p1s.php", {
                    method: "POST",
                    body: formData
                });

                console.log("📩 Response status:", response.status);
                const result = await response.json();
                console.log("📦 JSON result:", result);

                uploadResult.textContent = result.message || "Upload complete.";
                uploadResult.style.color = "green";

                if (typeof loadTables === "function") {
                    loadTables();
                }
            } catch (error) {
                console.error("❌ Upload error:", error);
                uploadResult.textContent = "❌ Upload failed: " + error;
                uploadResult.style.color = "red";
            }
        });
    } else {
        console.warn("⚠️ csvForm not found in DOM");
    }

    // --- TABLE CHANGE ---
    if (tableSelect) {
        tableSelect.addEventListener('change', () => {
            currentTable = tableSelect.value;
            fetchData(currentTable);
        });
    }

    // --- BULK BUTTONS ---
    if (bulkEligible) bulkEligible.addEventListener('click', () => bulkUpdate('Eligible'));
    if (bulkIneligible) bulkIneligible.addEventListener('click', () => bulkUpdate('Ineligible'));

    // --- INITIAL FETCH ---
    fetchData('zsmMEMBERS');
    fetchData(tableSelect ? tableSelect.value : 'registeredVOTERS');
});
