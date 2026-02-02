    fetch('/stats', {
                method: "POST"
        })
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {

            document.getElementById('users-count').innerText = data.general.users;
            document.getElementById('reels-count').innerText = data.general.reels;

            generateUsersTable(data.users_list);

            generateChart(data.chart);
        })
        .catch(error => {
            console.error('Błąd:', error);
            document.getElementById('users-count').innerText = "Error";
        });

function generateUsersTable(users) {
    const tbody = document.getElementById('users-table-body');
    tbody.innerHTML = ''; 

    if (!users || users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center">No users found.</td></tr>';
        return;
    }

    users.forEach(user => {
        let deleteButton = '';
        if (user.role !== 1) {
            deleteButton = `
                <form method="POST" action="deleteUser" onsubmit="return confirm('Delete user?');" style="display:inline;">
                    <input type="hidden" name="user_id" value="${user.id}">
                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
            `;
        }

        const row = `
            <tr>
                <td>${user.id}</td>
                <td>${escapeHtml(user.email)}</td>
                <td>
                    <form action="updateUser" method="POST" class="d-flex gap-2">
                        <input type="hidden" name="user_id" value="${user.id}">
                        <input type="text" name="username" value="${escapeHtml(user.username)}" 
                               class="form-control form-control-sm" style="width: 100px;" required>
                        <button type="submit" class="btn btn-sm btn-success">✓</button>
                    </form>
                </td>
                <td>${user.reels_count || 0}</td>
                <td>${deleteButton}</td>
            </tr>
        `;
        tbody.innerHTML += row;
    });
}

function generateChart(statsData) {
    const canvas = document.getElementById('countryChart');
    if (!canvas) return;

    if (!statsData || statsData.length === 0) {
        const ctx = canvas.getContext('2d');
        ctx.font = "16px sans-serif";
        ctx.textAlign = "center";
        ctx.fillText("No statistics available yet", canvas.width / 2, canvas.height / 2);
        return;
    }

    const ctx = canvas.getContext('2d');
    const labels = statsData.map(item => item.country_name);
    const dataValues = statsData.map(item => item.percentage_share);

    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                label: '% Share',
                data: dataValues,
                backgroundColor: [
                    '#4a477a', '#6c5ce7', '#a29bfe', '#dfe6e9',
                    '#4b4b4b', '#848484', '#2a2232', '#4c3675'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });
}

function escapeHtml(text) {
    if (!text) return "";
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}