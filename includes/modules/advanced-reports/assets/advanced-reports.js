/**
 * JavaScript para Informes y Analíticas Avanzadas
 */

(function ($) {
    'use strict';

    /**
     * Calcula regresión lineal y devuelve puntos de tendencia
     * @param {number[]} values - Datos Y (uno por mes)
     * @returns {number[]} Valores de la línea de tendencia
     */
    function linearRegression(values) {
        var n = values.length;
        if (n < 2) return values.slice();
        var sumX = 0, sumY = 0, sumXY = 0, sumX2 = 0;
        for (var i = 0; i < n; i++) {
            sumX += i;
            sumY += values[i];
            sumXY += i * values[i];
            sumX2 += i * i;
        }
        var denom = n * sumX2 - sumX * sumX;
        var m = denom !== 0 ? (n * sumXY - sumX * sumY) / denom : 0;
        var b = (sumY - m * sumX) / n;
        var out = [];
        for (var j = 0; j < n; j++) {
            out.push(m * j + b);
        }
        return out;
    }

    const ReportsApp = {
        charts: {},
        currentYear: alquipressReports.currentYear,
        breakdownView: 'property',
        refreshCooldownMs: 7000,
        lastRefreshAt: 0,
        refreshTimer: null,
        isRefreshing: false,

        init: function () {
            var activeBreakdown = $('.ap-reports-filter-pill.active').data('view');
            if (activeBreakdown) {
                this.breakdownView = activeBreakdown.toString();
            }
            $('#report-year').val(this.currentYear);
            this.bindEvents();
            this.loadAllReports();
        },

        showToast: function (type, message) {
            if (typeof AlquipressToast !== 'undefined' && typeof AlquipressToast[type] === 'function') {
                AlquipressToast[type](message);
                return;
            }
            if (type === 'error') {
                console.error(message);
                return;
            }
            console.log(message);
        },

        bindEvents: function () {
            // Cambio de tabs
            $('.tab-button').on('click', function () {
                const tabId = $(this).data('tab');
                ReportsApp.switchTab(tabId);
            });

            // Filtros del gráfico de desglose
            $('.ap-reports-filter-pill').on('click', function () {
                var view = ($(this).data('view') || '').toString();
                if (!view || view === ReportsApp.breakdownView) {
                    return;
                }
                ReportsApp.breakdownView = view;
                $('.ap-reports-filter-pill').removeClass('active');
                $(this).addClass('active');
                ReportsApp.loadRevenueBreakdownMainChart();
            });

            // Refresh reports
            $('#refresh-reports').on('click', function () {
                ReportsApp.refreshReports();
            });

            // Export Excel (CSV)
            $('#export-excel').on('click', function () {
                var year = $('#report-year').val() || ReportsApp.currentYear;
                var form = $('<form>').attr({
                    method: 'POST',
                    action: alquipressReports.ajaxurl,
                    style: 'display:none'
                });
                form.append($('<input>').attr({ type: 'hidden', name: 'action', value: 'alquipress_export_reports_csv' }));
                form.append($('<input>').attr({ type: 'hidden', name: 'nonce', value: alquipressReports.nonce }));
                form.append($('<input>').attr({ type: 'hidden', name: 'year', value: year }));
                $('body').append(form);
                form.submit();
                form.remove();
            });

            // Export PDF (impresión)
            $('#export-pdf').on('click', function () {
                $('body').addClass('ap-reports-print-mode');
                window.print();
                setTimeout(function () {
                    $('body').removeClass('ap-reports-print-mode');
                }, 500);
            });

            // Enviar por email
            $('#email-report').on('click', function () {
                ReportsApp.emailReport();
            });
        },

        refreshReports: function () {
            if (this.isRefreshing) {
                this.showToast('warning', (alquipressReports.i18n && alquipressReports.i18n.refreshCooldown) ? alquipressReports.i18n.refreshCooldown : 'Espera unos segundos antes de volver a actualizar.');
                return;
            }

            var now = Date.now();
            if (now - this.lastRefreshAt < this.refreshCooldownMs) {
                this.showToast('warning', (alquipressReports.i18n && alquipressReports.i18n.refreshCooldown) ? alquipressReports.i18n.refreshCooldown : 'Espera unos segundos antes de volver a actualizar.');
                return;
            }

            this.isRefreshing = true;
            this.lastRefreshAt = now;
            this.currentYear = $('#report-year').val() || this.currentYear;
            this.loadAllReports();

            var $refreshBtn = $('#refresh-reports');
            var self = this;
            $refreshBtn.prop('disabled', true).addClass('is-disabled');
            if (this.refreshTimer) {
                clearTimeout(this.refreshTimer);
            }
            this.refreshTimer = setTimeout(function () {
                $refreshBtn.prop('disabled', false).removeClass('is-disabled');
                self.isRefreshing = false;
            }, this.refreshCooldownMs);
        },

        emailReport: function () {
            var initialEmail = alquipressReports.currentUserEmail || '';
            var promptText = (alquipressReports.i18n && alquipressReports.i18n.emailPrompt) ? alquipressReports.i18n.emailPrompt : 'Introduce el email de destino para enviar el informe.';
            var email = window.prompt(promptText, initialEmail);

            if (email === null) {
                return;
            }

            email = $.trim(email);
            if (!email || !/.+@.+\..+/.test(email)) {
                this.showToast('error', (alquipressReports.i18n && alquipressReports.i18n.emailDefaultInvalid) ? alquipressReports.i18n.emailDefaultInvalid : 'Email no válido.');
                return;
            }

            var year = $('#report-year').val() || this.currentYear;
            var $emailBtn = $('#email-report');
            $emailBtn.prop('disabled', true).addClass('is-disabled');
            this.showToast('info', (alquipressReports.i18n && alquipressReports.i18n.emailSending) ? alquipressReports.i18n.emailSending : 'Enviando informe por email...');

            $.ajax({
                url: alquipressReports.ajaxurl,
                method: 'POST',
                data: {
                    action: 'alquipress_email_report',
                    nonce: alquipressReports.nonce,
                    year: year,
                    email: email
                }
            }).done(function (response) {
                if (response && response.success) {
                    ReportsApp.showToast('success', (response.data && response.data.message) ? response.data.message : ((alquipressReports.i18n && alquipressReports.i18n.emailSent) ? alquipressReports.i18n.emailSent : 'Informe enviado correctamente.'));
                    return;
                }
                var msg = (response && response.data && response.data.message) ? response.data.message : 'No se pudo enviar el email.';
                ReportsApp.showToast('error', msg);
            }).fail(function () {
                ReportsApp.showToast('error', (alquipressReports.i18n && alquipressReports.i18n.errorConnection) ? alquipressReports.i18n.errorConnection : 'Error de conexión al cargar los datos.');
            }).always(function () {
                $emailBtn.prop('disabled', false).removeClass('is-disabled');
            });
        },

        switchTab: function (tabId) {
            $('.tab-button').removeClass('active');
            $('.tab-button[data-tab="' + tabId + '"]').addClass('active');

            $('.tab-content').removeClass('active');
            $('#tab-' + tabId).addClass('active');
        },

        loadAllReports: function () {
            this.loadOverviewYoy();
            this.loadRevenueMonthly();
            this.loadRevenueBreakdownMainChart();
            this.loadRevenueSeason();
            this.loadOccupancyMonthly();
            this.loadOccupancyComparison();
            this.loadTopClients();
            this.loadClientsRating();
            this.loadTopProperties();
            this.loadBookingPerformance();
            this.loadPropertiesComparison();
        },

        ajaxCall: function (reportType, callback) {
            $.ajax({
                url: alquipressReports.ajaxurl,
                method: 'POST',
                data: {
                    action: 'alquipress_get_report_data',
                    nonce: alquipressReports.nonce,
                    report_type: reportType,
                    year: ReportsApp.currentYear
                },
                success: function (response) {
                    if (response.success) {
                        callback(response.data);
                    } else {
                        var msg = (response.data && response.data.message) ? response.data.message : ('Error al cargar reporte: ' + reportType);
                        ReportsApp.showToast('error', msg);
                    }
                },
                error: function (xhr, status, error) {
                    var msg = (alquipressReports.i18n && alquipressReports.i18n.errorConnection) ? alquipressReports.i18n.errorConnection : 'Error de conexión al cargar los datos.';
                    ReportsApp.showToast('error', msg);
                    console.error('Error AJAX:', error);
                }
            });
        },

        renderChartById: function (id, config) {
            var el = document.getElementById(id);
            if (!el) {
                return;
            }
            if (ReportsApp.charts[id]) {
                ReportsApp.charts[id].destroy();
                ReportsApp.charts[id] = null;
            }
            ReportsApp.charts[id] = new Chart(el.getContext('2d'), config);
        },

        loadRevenueBreakdownMainChart: function () {
            if (this.breakdownView === 'month') {
                this.loadRevenueMonthlyMain();
                return;
            }
            this.loadRevenueByPropertyBreakdown();
        },

        loadRevenueMonthlyMain: function () {
            this.ajaxCall('revenue_monthly_yoy', function (data) {
                ReportsApp.renderChartById('chart-revenue-monthly', ReportsApp.buildRevenueMonthlyChartConfig(data));
            });
        },

        loadRevenueByPropertyBreakdown: function () {
            this.ajaxCall('top_properties', function (data) {
                var labels = [];
                var values = [];
                (data || []).forEach(function (item) {
                    labels.push(item.name || '—');
                    values.push(Number(item.total_revenue || 0));
                });

                if (!labels.length) {
                    labels = ['Sin datos'];
                    values = [0];
                }

                var config = {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Ingresos (€)',
                            data: values,
                            backgroundColor: 'rgba(44, 153, 226, 0.75)',
                            borderColor: '#2c99e2',
                            borderWidth: 1,
                            borderRadius: 6,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        return 'Ingresos: ' + Number(context.parsed.y || 0).toFixed(2) + ' €';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function (value) {
                                        return Number(value).toLocaleString() + ' €';
                                    }
                                }
                            }
                        }
                    }
                };
                ReportsApp.renderChartById('chart-revenue-monthly', config);
            });
        },

        // ========== Cargar Estadísticas Overview (con YoY) ==========

        loadOverviewYoy: function () {
            this.ajaxCall('overview_yoy', function (data) {
                $('#stat-revenue-year').html(data.total_revenue || '—');
                $('#stat-bookings-year').text(data.total_bookings !== undefined ? data.total_bookings : '—');
                $('#stat-occupancy-rate').text(data.occupancy_rate || '—');
                $('#stat-avg-daily-rate').html(data.avg_daily_rate || '—');
                var revCh = data.revenue_change;
                $('#stat-revenue-change').text(revCh != null ? (revCh >= 0 ? '+' : '') + revCh + '% YoY' : '—').toggleClass('ap-reports-change-negative', revCh < 0);
                var bookCh = data.bookings_change;
                $('#stat-bookings-change').text(bookCh != null ? (bookCh >= 0 ? '+' : '') + bookCh + '% ' + (alquipressReports.i18n && alquipressReports.i18n.vsLastYear ? alquipressReports.i18n.vsLastYear : 'vs año ant.') : '—').toggleClass('ap-reports-change-negative', bookCh < 0);
                var occCh = data.occupancy_change;
                $('#stat-occupancy-change').text(occCh != null ? (occCh >= 0 ? '+' : '') + occCh + '% ' + (alquipressReports.i18n && alquipressReports.i18n.vsAvg ? alquipressReports.i18n.vsAvg : 'vs media') : '—').toggleClass('ap-reports-change-negative', occCh < 0);
                var adrCh = data.avg_daily_rate_change;
                $('#stat-adr-change').text(adrCh != null ? (adrCh >= 0 ? '+' : '') + adrCh + '% YoY' : '—').toggleClass('ap-reports-change-negative', adrCh < 0);
            });
        },

        // ========== Cargar Ingresos Mensuales (con YoY) ==========

        loadRevenueMonthly: function () {
            this.ajaxCall('revenue_monthly_yoy', function (data) {
                ReportsApp.renderChartById('chart-revenue-monthly-tab', ReportsApp.buildRevenueMonthlyChartConfig(data));
            });
        },

        buildRevenueMonthlyChartConfig: function (data) {
            var trendData = linearRegression(data.data);
            var datasets = [{
                label: (data.year || ReportsApp.currentYear) + ' (€)',
                data: data.data,
                borderColor: '#2c99e2',
                backgroundColor: 'rgba(44, 153, 226, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#2c99e2',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }];
            if (data.data_prev && data.data_prev.length) {
                datasets.push({
                    label: (data.year_prev || (ReportsApp.currentYear - 1)) + ' (€)',
                    data: data.data_prev,
                    borderColor: '#94a3b8',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    fill: false,
                    tension: 0.4,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    pointBackgroundColor: '#94a3b8',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2
                });
            }
            datasets.push({
                label: (alquipressReports.i18n && alquipressReports.i18n.trend) ? alquipressReports.i18n.trend : 'Tendencia',
                data: trendData,
                borderColor: '#f59e0b',
                backgroundColor: 'transparent',
                borderWidth: 2,
                borderDash: [8, 4],
                fill: false,
                tension: 0,
                pointRadius: 0,
                pointHoverRadius: 0
            });
            return {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: true },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return 'Ingresos: ' + context.parsed.y.toFixed(2) + ' €';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (value) {
                                    return value.toLocaleString() + ' €';
                                }
                            }
                        }
                    }
                }
            };
        },

        // ========== Cargar Ingresos por Temporada ==========

        loadRevenueSeason: function () {
            this.ajaxCall('revenue_season', function (data) {
                if (ReportsApp.charts.revenueSeason) {
                    ReportsApp.charts.revenueSeason.destroy();
                }

                const ctx = document.getElementById('chart-revenue-season').getContext('2d');

                ReportsApp.charts.revenueSeason = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.data,
                            backgroundColor: [
                                'rgba(102, 126, 234, 0.8)',
                                'rgba(245, 87, 108, 0.8)',
                                'rgba(67, 233, 123, 0.8)'
                            ],
                            borderColor: '#fff',
                            borderWidth: 3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        const label = context.label || '';
                                        const value = context.parsed || 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percentage = ((value / total) * 100).toFixed(1);
                                        return label + ': ' + value.toFixed(2) + ' € (' + percentage + '%)';
                                    }
                                }
                            }
                        }
                    }
                });
            });
        },

        // ========== Cargar Ocupación Mensual ==========

        loadOccupancyMonthly: function () {
            this.ajaxCall('occupancy_monthly', function (data) {
                if (ReportsApp.charts.occupancyMonthly) {
                    ReportsApp.charts.occupancyMonthly.destroy();
                }

                var trendData = linearRegression(data.data);
                var ctx = document.getElementById('chart-occupancy-monthly').getContext('2d');

                ReportsApp.charts.occupancyMonthly = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                label: 'Ocupación (%)',
                                data: data.data,
                                backgroundColor: 'rgba(67, 233, 123, 0.8)',
                                borderColor: '#43e97b',
                                borderWidth: 2
                            },
                            {
                                label: (alquipressReports.i18n && alquipressReports.i18n.trend) ? alquipressReports.i18n.trend : 'Tendencia',
                                data: trendData,
                                type: 'line',
                                borderColor: '#f59e0b',
                                backgroundColor: 'transparent',
                                borderWidth: 2,
                                borderDash: [8, 4],
                                fill: false,
                                tension: 0,
                                pointRadius: 0,
                                pointHoverRadius: 0
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        if (context.dataset.type === 'line') {
                                            return 'Tendencia: ' + context.parsed.y.toFixed(1) + '%';
                                        }
                                        return 'Ocupación: ' + context.parsed.y.toFixed(1) + '%';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    callback: function (value) {
                                        return value + '%';
                                    }
                                }
                            }
                        }
                    }
                });
            });
        },

        // ========== Cargar Comparación de Ocupación ==========

        loadOccupancyComparison: function () {
            this.ajaxCall('occupancy_comparison', function (data) {
                if (ReportsApp.charts.occupancyComparison) {
                    ReportsApp.charts.occupancyComparison.destroy();
                }

                const ctx = document.getElementById('chart-occupancy-comparison').getContext('2d');

                ReportsApp.charts.occupancyComparison = new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.data,
                            backgroundColor: [
                                'rgba(67, 233, 123, 0.8)',
                                'rgba(240, 240, 241, 0.8)'
                            ],
                            borderColor: '#fff',
                            borderWidth: 3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'bottom'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        const label = context.label || '';
                                        const value = context.parsed || 0;
                                        return label + ': ' + value.toLocaleString() + ' noches';
                                    }
                                }
                            }
                        }
                    }
                });
            });
        },

        // ========== Cargar Top Clientes ==========

        loadTopClients: function () {
            this.ajaxCall('top_clients', function (data) {
                const tbody = $('#table-top-clients tbody');
                tbody.empty();

                if (data.length === 0) {
                    tbody.append('<tr><td colspan="6" style="text-align: center;">No hay datos disponibles</td></tr>');
                    return;
                }

                data.forEach(function (client, index) {
                    const position = index + 1;
                    const positionClass = 'position-' + position;
                    const lastOrderDate = new Date(client.last_order_date).toLocaleDateString('es-ES');

                    const row = `
                        <tr>
                            <td><span class="position-badge ${positionClass}">${position}</span></td>
                            <td><strong>${client.name}</strong></td>
                            <td>${client.email}</td>
                            <td>${client.total_orders}</td>
                            <td><strong>${client.total_spent.toFixed(2)} €</strong></td>
                            <td>${lastOrderDate}</td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            });
        },

        // ========== Cargar Distribución de Clientes por Rating ==========

        loadClientsRating: function () {
            this.ajaxCall('clients_rating', function (data) {
                if (ReportsApp.charts.clientsRating) {
                    ReportsApp.charts.clientsRating.destroy();
                }

                const ctx = document.getElementById('chart-clients-rating').getContext('2d');

                ReportsApp.charts.clientsRating = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Número de Clientes',
                            data: data.data,
                            backgroundColor: [
                                'rgba(220, 50, 50, 0.8)',
                                'rgba(240, 184, 73, 0.8)',
                                'rgba(74, 172, 254, 0.8)',
                                'rgba(102, 126, 234, 0.8)',
                                'rgba(67, 233, 123, 0.8)'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            });
        },

        // ========== Cargar Top Propiedades ==========

        loadBookingPerformance: function () {
            this.ajaxCall('top_properties', function (data) {
                var tbody = $('#table-booking-performance tbody');
                tbody.empty();
                if (!data || data.length === 0) {
                    tbody.append('<tr><td colspan="5">No hay datos disponibles</td></tr>');
                    return;
                }
                data.forEach(function (p) {
                    var rev = (p.total_revenue || 0).toFixed(2).replace('.', ',') + ' €';
                    var occ = (p.occupancy_rate != null ? p.occupancy_rate.toFixed(1) : '—') + '%';
                    var trend = '<span class="ap-reports-trend-up">↑</span>';
                    tbody.append(
                        '<tr><td>' + (p.name || '—') + '</td><td class="ap-reports-th-num">' + (p.total_bookings || 0) + '</td><td class="ap-reports-th-num">' + rev + '</td><td class="ap-reports-th-num">' + occ + '</td><td class="ap-reports-th-trend">' + trend + '</td></tr>'
                    );
                });
            });
        },

        loadTopProperties: function () {
            this.ajaxCall('top_properties', function (data) {
                const tbody = $('#table-top-properties tbody');
                tbody.empty();

                if (data.length === 0) {
                    tbody.append('<tr><td colspan="6" style="text-align: center;">No hay datos disponibles</td></tr>');
                    return;
                }

                data.forEach(function (property, index) {
                    const position = index + 1;
                    const positionClass = 'position-' + position;

                    const row = `
                        <tr>
                            <td><span class="position-badge ${positionClass}">${position}</span></td>
                            <td><strong>${property.name}</strong></td>
                            <td>${property.total_bookings}</td>
                            <td>${property.total_nights} noches</td>
                            <td><strong>${property.total_revenue.toFixed(2)} €</strong></td>
                            <td>${property.occupancy_rate.toFixed(1)}%</td>
                        </tr>
                    `;
                    tbody.append(row);
                });
            });
        },

        // ========== Cargar Comparativa de Propiedades ==========

        loadPropertiesComparison: function () {
            this.ajaxCall('properties_comparison', function (data) {
                if (ReportsApp.charts.propertiesComparison) {
                    ReportsApp.charts.propertiesComparison.destroy();
                }

                const ctx = document.getElementById('chart-properties-comparison').getContext('2d');

                ReportsApp.charts.propertiesComparison = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Ingresos (€)',
                            data: data.data,
                            backgroundColor: 'rgba(102, 126, 234, 0.8)',
                            borderColor: '#667eea',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        return 'Ingresos: ' + context.parsed.x.toFixed(2) + ' €';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function (value) {
                                        return value.toLocaleString() + ' €';
                                    }
                                }
                            }
                        }
                    }
                });
            });
        }
    };

    // Inicializar cuando el documento esté listo
    $(document).ready(function () {
        if ($('.alquipress-reports-page, .alquipress-reports-wrap').length) {
            ReportsApp.init();
        }
    });

})(jQuery);
