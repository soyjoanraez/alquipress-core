/**
 * JavaScript para Informes y Analíticas Avanzadas
 */

(function ($) {
    'use strict';

    const ReportsApp = {
        charts: {},
        currentYear: alquipressReports.currentYear,

        init: function () {
            this.bindEvents();
            this.loadAllReports();
        },

        bindEvents: function () {
            // Cambio de tabs
            $('.tab-button').on('click', function () {
                const tabId = $(this).data('tab');
                ReportsApp.switchTab(tabId);
            });

            // Refresh reports
            $('#refresh-reports').on('click', function () {
                ReportsApp.currentYear = $('#report-year').val();
                ReportsApp.loadAllReports();
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
                        console.error('Error al cargar reporte:', reportType);
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error AJAX:', error);
                }
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

        // ========== Cargar Ingresos Mensuales ==========

        loadRevenueMonthly: function () {
            this.ajaxCall('revenue_monthly', function (data) {
                var chartConfig = {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Ingresos (€)',
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
                ['chart-revenue-monthly', 'chart-revenue-monthly-tab'].forEach(function (id) {
                    var el = document.getElementById(id);
                    if (!el) return;
                    if (ReportsApp.charts[id]) {
                        ReportsApp.charts[id].destroy();
                        ReportsApp.charts[id] = null;
                    }
                    ReportsApp.charts[id] = new Chart(el.getContext('2d'), chartConfig);
                });
            });
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

                const ctx = document.getElementById('chart-occupancy-monthly').getContext('2d');

                ReportsApp.charts.occupancyMonthly = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Ocupación (%)',
                            data: data.data,
                            backgroundColor: 'rgba(67, 233, 123, 0.8)',
                            borderColor: '#43e97b',
                            borderWidth: 2
                        }]
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
