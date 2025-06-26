document.addEventListener('DOMContentLoaded', function() {
    // Set Chart.js defaults
    Chart.defaults.font.family = "'Poppins', sans-serif";
    Chart.defaults.color = '#6c757d';
    
    // Get chart data from PHP
    const activityData = JSON.parse(document.getElementById('activity-data').textContent);
    const categoriesData = JSON.parse(document.getElementById('categories-data').textContent);
    const statusData = JSON.parse(document.getElementById('status-data').textContent);
    const loanStatusData = JSON.parse(document.getElementById('loan-status-data').textContent);
    
    // 1. Daily Borrowing Activity Chart (replacing Monthly)
    const activityCtx = document.getElementById('activity-chart').getContext('2d');
    const activityLabels = Object.keys(activityData);
    const activityValues = Object.values(activityData);
    
    const activityChart = new Chart(activityCtx, {
        type: 'line',
        data: {
            labels: activityLabels,
            datasets: [
                {
                    label: 'Borrowing Activity',
                    data: activityValues,
                    borderColor: '#4361ee',
                    backgroundColor: 'rgba(67, 97, 238, 0.1)',
                    tension: 0.3,
                    fill: true,
                    pointBackgroundColor: '#4361ee',
                    pointRadius: 4,
                    pointHoverRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        title: function(tooltipItems) {
                            return tooltipItems[0].label;
                        },
                        label: function(context) {
                            return `Books borrowed: ${context.parsed.y}`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Loans'
                    },
                    ticks: {
                        stepSize: 1,
                        precision: 0
                    }
                }
            }
        }
    });
    
    // 2. Book Categories Chart
    const categoriesCtx = document.getElementById('categories-chart').getContext('2d');
    const categoryLabels = Object.keys(categoriesData);
    const categoryValues = Object.values(categoriesData);
    
    const chartColors = [
        'rgba(67, 97, 238, 0.7)',
        'rgba(63, 55, 201, 0.7)',
        'rgba(9, 188, 138, 0.7)',
        'rgba(76, 201, 240, 0.7)',
        'rgba(249, 199, 79, 0.7)',
        'rgba(230, 57, 70, 0.7)',
        'rgba(142, 84, 233, 0.7)',
        'rgba(245, 123, 32, 0.7)'
    ];
    
    const categoriesChart = new Chart(categoriesCtx, {
        type: 'pie',
        data: {
            labels: categoryLabels,
            datasets: [{
                data: categoryValues,
                backgroundColor: chartColors.slice(0, categoryLabels.length),
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 15
                    }
                }
            }
        }
    });
    
    // 3. Book Status Chart
    const statusCtx = document.getElementById('status-chart').getContext('2d');
    const statusLabels = Object.keys(statusData);
    const statusValues = Object.values(statusData);
    
    const statusChart = new Chart(statusCtx, {
        type: 'bar',
        data: {
            labels: statusLabels,
            datasets: [{
                label: 'Number of Books',
                data: statusValues,
                backgroundColor: [
                    'rgba(9, 188, 138, 0.7)',
                    'rgba(230, 57, 70, 0.7)'
                ],
                borderColor: [
                    'rgba(9, 188, 138, 1)',
                    'rgba(230, 57, 70, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Books'
                    }
                }
            }
        }
    });
    
    // 4. Loan Status Chart
    const loanStatusCtx = document.getElementById('loan-status-chart').getContext('2d');
    const loanStatusLabels = Object.keys(loanStatusData);
    const loanStatusValues = Object.values(loanStatusData);
    
    const loanStatusColors = {
        'Reserved': 'rgba(249, 199, 79, 0.7)',
        'Ready for pickup': 'rgba(76, 201, 240, 0.7)',
        'Borrowed': 'rgba(9, 188, 138, 0.7)',
        'Returned': 'rgba(142, 84, 233, 0.7)',
        'Overdue': 'rgba(230, 57, 70, 0.7)'
    };
    
    const loanStatusBorderColors = {
        'Reserved': 'rgba(249, 199, 79, 1)',
        'Ready for pickup': 'rgba(76, 201, 240, 1)',
        'Borrowed': 'rgba(9, 188, 138, 1)',
        'Returned': 'rgba(142, 84, 233, 1)',
        'Overdue': 'rgba(230, 57, 70, 1)'
    };
    
    const loanStatusChart = new Chart(loanStatusCtx, {
        type: 'doughnut',
        data: {
            labels: loanStatusLabels,
            datasets: [{
                data: loanStatusValues,
                backgroundColor: loanStatusLabels.map(label => loanStatusColors[label] || 'rgba(67, 97, 238, 0.7)'),
                borderColor: loanStatusLabels.map(label => loanStatusBorderColors[label] || 'rgba(67, 97, 238, 1)'),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 15
                    }
                }
            }
        }
    });
    
    // 5. Most Borrowed Categories Chart
    try {
        const borrowedCategoriesData = JSON.parse(document.getElementById('borrowed-categories-data').textContent);
        const borrowedCategoriesCtx = document.getElementById('borrowed-categories-chart').getContext('2d');
        const borrowedCategoryLabels = Object.keys(borrowedCategoriesData);
        const borrowedCategoryValues = Object.values(borrowedCategoriesData);
        
        // Generate a nice gradient color scheme for the bars
        const categoryGradients = [];
        for (let i = 0; i < borrowedCategoryLabels.length; i++) {
            const gradientColor = borrowedCategoriesCtx.createLinearGradient(0, 0, 0, 400);
            // Use different hues based on position to create visual distinction
            const hue = (180 + i * 40) % 360; // Cycle through colors
            gradientColor.addColorStop(0, `hsla(${hue}, 80%, 60%, 0.8)`);
            gradientColor.addColorStop(1, `hsla(${hue}, 80%, 45%, 0.8)`);
            categoryGradients.push(gradientColor);
        }
        
        const borrowedCategoriesChart = new Chart(borrowedCategoriesCtx, {
            type: 'bar',
            data: {
                labels: borrowedCategoryLabels,
                datasets: [{
                    label: 'Number of Borrows',
                    data: borrowedCategoryValues,
                    backgroundColor: categoryGradients,
                    borderWidth: 0,
                    borderRadius: 6,
                    maxBarThickness: 50
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            title: function(tooltipItems) {
                                return tooltipItems[0].label;
                            },
                            label: function(context) {
                                return `Borrowed: ${context.parsed.y} times`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Borrows'
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error loading Most Borrowed Categories chart:', error);
    }

    // 6. Most Borrowed Books Chart
    try {
        const borrowedBooksData = JSON.parse(document.getElementById('borrowed-books-data').textContent);
        const borrowedBooksAuthors = JSON.parse(document.getElementById('borrowed-books-authors').textContent);
        const borrowedBooksCtx = document.getElementById('borrowed-books-chart').getContext('2d');
        const borrowedBooksLabels = Object.keys(borrowedBooksData);
        const borrowedBooksValues = Object.values(borrowedBooksData);
        
        // Create a gradient for the bars
        const booksGradient = borrowedBooksCtx.createLinearGradient(0, 0, 400, 0);
        booksGradient.addColorStop(0, 'rgba(63, 55, 201, 0.8)');
        booksGradient.addColorStop(1, 'rgba(104, 109, 224, 0.8)');
        
        const borrowedBooksChart = new Chart(borrowedBooksCtx, {
            type: 'bar',
            data: {
                labels: borrowedBooksLabels,
                datasets: [{
                    label: 'Borrow Count',
                    data: borrowedBooksValues,
                    backgroundColor: booksGradient,
                    borderColor: 'rgba(63, 55, 201, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                    maxBarThickness: 25
                }]
            },
            options: {
                indexAxis: 'y',  // Horizontal bar chart
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            title: function(tooltipItems) {
                                return tooltipItems[0].label;
                            },
                            afterTitle: function(tooltipItems) {
                                const bookTitle = tooltipItems[0].label;
                                return `by ${borrowedBooksAuthors[bookTitle]}`;
                            },
                            label: function(context) {
                                return `Borrowed: ${context.parsed.x} times`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Borrows'
                        }
                    },
                    y: {
                        ticks: {
                            callback: function(value) {
                                const label = this.getLabelForValue(value);
                                // Truncate long book titles in the Y axis labels
                                if (label.length > 20) {
                                    return label.substr(0, 17) + '...';
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error loading Most Borrowed Books chart:', error);
    }
    
    // Handle chart type change for categories chart
    document.getElementById('category-chart-type').addEventListener('change', function() {
        const newType = this.value;
        categoriesChart.config.type = newType;
        
        // Adjust legend position based on chart type
        if (newType === 'bar') {
            categoriesChart.options.plugins.legend.display = false;
            categoriesChart.options.indexAxis = 'y'; // Horizontal bar chart
        } else {
            categoriesChart.options.plugins.legend.display = true;
            categoriesChart.options.plugins.legend.position = 'right';
            if (newType === 'pie' || newType === 'doughnut') {
                delete categoriesChart.options.indexAxis;
            }
        }
        
        categoriesChart.update();
    });
});