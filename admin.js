// admin.js
document.addEventListener('DOMContentLoaded', function() {
    // Password toggle functionality
    const eyeIcon = document.getElementById('eyeicon');
    const passwordField = document.getElementById('password');
    
    if (eyeIcon && passwordField) {
        eyeIcon.addEventListener('click', function() {
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.src = 'img/show_pass.png';
            } else {
                passwordField.type = 'password';
                eyeIcon.src = 'img/hide_pass.png';
            }
        });
    }
    
    
    // Chart rendering (placeholder)
    const chartElement = document.getElementById('borrowing-chart');
    const chartPeriodSelector = document.getElementById('chart-period');
    
    if (chartElement && chartPeriodSelector) {
        // Function to simulate chart data loading
        function updateChartDisplay(period) {
            chartElement.innerHTML = `<div class="chart-loading">Loading ${period} borrowing data...</div>`;
            
            // Simulate loading delay
            setTimeout(() => {
                chartElement.innerHTML = `
                    <div style="height: 300px; display: flex; flex-direction: column;">
                        <div style="height: 270px; display: flex; align-items: flex-end; gap: 15px; padding-bottom: 20px;">
                            ${generateChartBars(period)}
                        </div>
                        <div style="height: 30px; display: flex; justify-content: space-between; padding: 0 10px;">
                            ${generateChartLabels(period)}
                        </div>
                    </div>
                `;
            }, 800);
        }
        
        function generateChartBars(period) {
            let bars = '';
            const count = period === 'week' ? 7 : period === 'month' ? 10 : 12;
            
            for (let i = 0; i < count; i++) {
                // Generate random height between 30 and 100%
                const height = Math.floor(Math.random() * 70) + 30;
                // Random number of books
                const books = Math.floor(Math.random() * 50) + 10;
                
                bars += `
                    <div style="flex: 1; height: ${height}%; background-color: #4361ee; border-radius: 5px; position: relative;">
                        <div style="position: absolute; top: -25px; width: 100%; text-align: center; font-size: 12px;">
                            ${books}
                        </div>
                    </div>
                `;
            }
            
            return bars;
        }
        
        function generateChartLabels(period) {
            let labels = '';
            
            if (period === 'week') {
                const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                days.forEach(day => {
                    labels += `<div style="flex: 1; text-align: center; font-size: 12px;">${day}</div>`;
                });
            } else if (period === 'month') {
                for (let i = 1; i <= 10; i++) {
                    const dayNum = i * 3;
                    labels += `<div style="flex: 1; text-align: center; font-size: 12px;">Jun ${dayNum}</div>`;
                }
            } else {
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                months.forEach(month => {
                    labels += `<div style="flex: 1; text-align: center; font-size: 12px;">${month}</div>`;
                });
            }
            
            return labels;
        }
        
        // Initial chart rendering
        updateChartDisplay('month');
        
        // Update chart when period changes
        chartPeriodSelector.addEventListener('change', function() {
            updateChartDisplay(this.value);
        });
    }
    
    // Add animations for stat cards
    const statCards = document.querySelectorAll('.stat-card');
    
    const observerOptions = {
        threshold: 0.1
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);
    
    statCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = `opacity 0.5s ease, transform 0.5s ease ${index * 0.1}s`;
        observer.observe(card);
    });
});
