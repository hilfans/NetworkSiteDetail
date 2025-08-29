document.addEventListener('DOMContentLoaded', function() {
    if (typeof nsd_data === 'undefined' || !nsd_data) {
        console.error('NSD Data not found or is empty.');
        const container = document.getElementById('nsd-dashboard-app');
        if(container) container.innerHTML = '<p>Error: Could not load site data.</p>';
        return;
    }

    let allSites = nsd_data.sites;
    let stats = nsd_data.stats;
    let chartData = nsd_data.chart_data;
    let currentView = 'grid';
    let currentPage = 1;
    let postChart = null;
    let isShowingActiveOnly = false;

    const elements = {
        chartCanvas: document.getElementById('nsd-posts-chart'),
        summaryContainer: document.getElementById('nsd-summary-cards'),
        dataContainer: document.getElementById('nsd-data-container'),
        searchInput: document.getElementById('nsd-search-input'),
        sortBy: document.getElementById('nsd-sort-by'),
        sortOrder: document.getElementById('nsd-sort-order'),
        yearFilter: document.getElementById('nsd-filter-year'),
        perPage: document.getElementById('nsd-per-page'),
        refreshBtn: document.getElementById('nsd-refresh-btn'),
        gridViewBtn: document.getElementById('nsd-grid-view-btn'),
        listViewBtn: document.getElementById('nsd-list-view-btn'),
        paginationContainer: document.getElementById('nsd-pagination'),
    };

    function init() {
        renderPostChart();
        renderSummaryCards();
        populateYearFilter();
        attachEventListeners();
        render();
    }

    function renderPostChart() {
        if (!elements.chartCanvas || !chartData || typeof Chart === 'undefined') return;
        const ctx = elements.chartCanvas.getContext('2d');
        if (postChart) postChart.destroy();
        postChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Total Posts per Year',
                    data: chartData.data,
                    borderColor: '#A31E21',
                    backgroundColor: 'rgba(163, 30, 33, 0.1)',
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Post Count' } },
                    x: { title: { display: true, text: 'Year' } }
                },
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: 'Pertumbuhan Konten Subsite per Tahun', font: { size: 18 }, color: '#333' }
                }
            }
        });
    }

    function renderSummaryCards() {
        elements.summaryContainer.innerHTML = `
            <div class="nsd-summary-card"><div class="label">Total Sites</div><div class="value">${stats.total_sites}</div></div>
            <div class="nsd-summary-card"><div class="label">Total Posts</div><div class="value">${stats.total_posts.toLocaleString()}</div></div>
            <div class="nsd-summary-card nsd-clickable" id="nsd-active-sites-card"><div class="label">Active Sites (Last 3 Months)</div><div class="value">${stats.active_sites}</div></div>
            <div class="nsd-summary-card"><div class="label">Avg Posts/Site</div><div class="value">${stats.avg_posts}</div></div>
        `;
        document.getElementById('nsd-active-sites-card').addEventListener('click', toggleActiveSitesFilter);
    }
    
    function populateYearFilter() {
        const years = [...new Set(allSites.map(site => new Date(site.registered).getFullYear()))].sort((a, b) => b - a);
        let optionsHtml = '<option value="all">All Years</option>';
        years.forEach(year => {
            optionsHtml += `<option value="${year}">${year}</option>`;
        });
        elements.yearFilter.innerHTML = optionsHtml;
    }

    function attachEventListeners() {
        ['sortBy', 'sortOrder', 'yearFilter', 'perPage'].forEach(id => {
            elements[id].addEventListener('change', () => { currentPage = 1; render(); });
        });
        elements.searchInput.addEventListener('keyup', () => { currentPage = 1; render(); });
        
        elements.refreshBtn.addEventListener('click', () => {
            elements.refreshBtn.textContent = 'Refreshing...';
            elements.refreshBtn.disabled = true;
            
            jQuery.post(nsd_ajax.ajax_url, {
                action: 'nsd_refresh_cache',
                nonce: nsd_ajax.nonce
            }, function(response) {
                if (response.success) {
                    window.nsd_data = response.data; 
                    allSites = window.nsd_data.sites;
                    stats = window.nsd_data.stats;
                    chartData = window.nsd_data.chart_data;
                    isShowingActiveOnly = false;
                    currentPage = 1;
                    init();
                }
            }).always(function() {
                elements.refreshBtn.textContent = 'Refresh Data';
                elements.refreshBtn.disabled = false;
            });
        });
        
        elements.gridViewBtn.addEventListener('click', () => setView('grid'));
        elements.listViewBtn.addEventListener('click', () => setView('list'));
    }

    function setView(view) {
        currentView = view;
        elements.gridViewBtn.classList.toggle('active', view === 'grid');
        elements.listViewBtn.classList.toggle('active', view === 'list');
        elements.dataContainer.className = view === 'grid' ? 'nsd-grid-view' : 'nsd-list-view';
        render();
    }

    function toggleActiveSitesFilter() {
        isShowingActiveOnly = !isShowingActiveOnly;
        document.getElementById('nsd-active-sites-card').classList.toggle('nsd-active-filter', isShowingActiveOnly);
        currentPage = 1;
        render();
    }

    function getFilteredAndSortedSites() {
        let sites = [...allSites];
        const searchTerm = elements.searchInput.value.toLowerCase();
        const year = elements.yearFilter.value;
        const threeMonthsAgo = new Date();
        threeMonthsAgo.setMonth(threeMonthsAgo.getMonth() - 3);

        if (isShowingActiveOnly) {
            sites = sites.filter(site => new Date(site.last_updated) > threeMonthsAgo);
        }
        if (searchTerm) {
            sites = sites.filter(site => site.name.toLowerCase().includes(searchTerm) || site.url.toLowerCase().includes(searchTerm));
        }
        if (year !== 'all') {
            sites = sites.filter(site => new Date(site.registered).getFullYear() == year);
        }

        const sortBy = elements.sortBy.value;
        const sortOrder = elements.sortOrder.value;
        sites.sort((a, b) => {
            let valA = a[sortBy];
            let valB = b[sortBy];
            if (sortBy === 'last_updated' || sortBy === 'registered') {
                valA = new Date(valA);
                valB = new Date(valB);
            }
            if (typeof valA === 'string') {
                valA = valA.toLowerCase();
                valB = valB.toLowerCase();
            }
            if (valA < valB) return sortOrder === 'asc' ? -1 : 1;
            if (valA > valB) return sortOrder === 'asc' ? 1 : -1;
            return 0;
        });

        return sites;
    }

    function render() {
        const filteredSites = getFilteredAndSortedSites();
        const perPage = parseInt(elements.perPage.value, 10);
        const totalPages = Math.ceil(filteredSites.length / perPage);
        
        if (currentPage > totalPages) {
            currentPage = totalPages || 1;
        }

        const start = (currentPage - 1) * perPage;
        const end = start + perPage;
        const paginatedSites = filteredSites.slice(start, end);

        renderData(paginatedSites);
        renderPagination(totalPages);
    }

    function renderData(sites) {
        if (sites.length === 0) {
            elements.dataContainer.innerHTML = '<p class="nsd-no-results">No sites found matching your criteria.</p>';
            return;
        }

        if (currentView === 'grid') {
            elements.dataContainer.innerHTML = sites.map(site => `
                <div class="nsd-card">
                    <div class="nsd-card-header">
                        <h2><a href="${site.home_url}" target="_blank" rel="noopener noreferrer">${site.name}</a></h2>
                        <p class="nsd-card-url">${site.url}</p>
                    </div>
                    <div class="nsd-card-body">
                        <div class="nsd-stat"><span class="nsd-stat-label">Users</span><span class="nsd-stat-value">${site.users.toLocaleString()}</span></div>
                        <div class="nsd-stat"><span class="nsd-stat-label">Posts</span><span class="nsd-stat-value">${site.post_count.toLocaleString()}</span></div>
                        <div class="nsd-stat nsd-stat-full"><span class="nsd-stat-label">Last Updated</span><span class="nsd-stat-value-small">${new Date(site.last_updated).toLocaleDateString()}</span></div>
                        <div class="nsd-stat nsd-stat-full"><span class="nsd-stat-label">Registered</span><span class="nsd-stat-value-small">${new Date(site.registered).toLocaleDateString()}</span></div>
                    </div>
                </div>
            `).join('');
        } else {
            const tableRows = sites.map(site => `
                <tr>
                    <td><a href="${site.home_url}" target="_blank" rel="noopener noreferrer">${site.name}</a><br><small>${site.url}</small></td>
                    <td>${new Date(site.last_updated).toLocaleDateString()}</td>
                    <td>${new Date(site.registered).toLocaleDateString()}</td>
                    <td>${site.users.toLocaleString()}</td>
                    <td>${site.post_count.toLocaleString()}</td>
                </tr>
            `).join('');
            elements.dataContainer.innerHTML = `
                <table class="nsd-list-table">
                    <thead><tr><th>Site Name</th><th>Last Updated</th><th>Registered</th><th>Users</th><th>Post Count</th></tr></thead>
                    <tbody>${tableRows}</tbody>
                </table>
            `;
        }
    }

    function renderPagination(totalPages) {
        if (totalPages <= 1) {
            elements.paginationContainer.innerHTML = '';
            return;
        }

        let paginationHtml = `
            <button class="nsd-pagination-btn" id="first-page" ${currentPage === 1 ? 'disabled' : ''}>&laquo; First</button>
            <button class="nsd-pagination-btn" id="prev-page" ${currentPage === 1 ? 'disabled' : ''}>&lsaquo; Prev</button>
            <span class="nsd-page-info">Page ${currentPage} of ${totalPages}</span>
            <button class="nsd-pagination-btn" id="next-page" ${currentPage === totalPages ? 'disabled' : ''}>Next &rsaquo;</button>
            <button class="nsd-pagination-btn" id="last-page" ${currentPage === totalPages ? 'disabled' : ''}>Last &raquo;</button>
        `;
        elements.paginationContainer.innerHTML = paginationHtml;

        document.getElementById('first-page').addEventListener('click', () => { if(currentPage !== 1) { currentPage = 1; render(); } });
        document.getElementById('prev-page').addEventListener('click', () => { if(currentPage > 1) { currentPage--; render(); } });
        document.getElementById('next-page').addEventListener('click', () => { if(currentPage < totalPages) { currentPage++; render(); } });
        document.getElementById('last-page').addEventListener('click', () => { if(currentPage !== totalPages) { currentPage = totalPages; render(); } });
    }

    init();
});
