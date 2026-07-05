document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.getElementById('searchForm');
    const resultsContainer = document.getElementById('pastQuestionsList');
    const modal = document.getElementById('questionModal');
    const modalBody = document.getElementById('modalBody');
    const closeBtn = document.querySelector('.close');
    
    // Search form submission
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        searchPastQuestions();
    });
    
    // Modal close events
    closeBtn.addEventListener('click', function() {
        modal.style.display = 'none';
    });
    
    window.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    function searchPastQuestions() {
        const formData = new FormData(searchForm);
        const params = new URLSearchParams();
        
        for (const [key, value] of formData) {
            if (value) params.append(key, value);
        }
        
        // In a real application, this would be an API call
        // For demo purposes, we'll use mock data
        const mockData = [
            {
                id: 1,
                title: 'Computer Programming Past Questions 2023',
                description: 'First semester computer programming questions',
                department: 'Computer Engineering',
                level: 'ND 1',
                semester: 'First Semester',
                year: 2023
            },
            {
                id: 2,
                title: 'Data Structures Past Questions 2023',
                description: 'Second semester data structures questions',
                department: 'Computer Engineering',
                level: 'ND 1',
                semester: 'Second Semester',
                year: 2023
            }
        ];
        
        displayResults(mockData);
    }
    
    function displayResults(questions) {
        if (questions.length === 0) {
            resultsContainer.innerHTML = '<p>No past questions found matching your criteria.</p>';
            return;
        }
        
        let html = '<div class="past-questions-grid">';
        
        questions.forEach(question => {
            html += `
                <div class="question-card">
                    <h3>${question.title}</h3>
                    <p><strong>Department:</strong> ${question.department}</p>
                    <p><strong>Level:</strong> ${question.level}</p>
                    <p><strong>Semester:</strong> ${question.semester}</p>
                    <p><strong>Year:</strong> ${question.year}</p>
                    <div class="question-actions">
                        <button class="btn btn-primary" onclick="viewQuestion(${question.id})">View</button>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        resultsContainer.innerHTML = html;
    }
    
    // Make function global so it can be called from inline onclick
    window.viewQuestion = function(questionId) {
        // In a real application, this would fetch the actual question content
        const mockContent = `
            <h2>Computer Programming Past Questions 2023</h2>
            <p><strong>Department:</strong> Computer Engineering</p>
            <p><strong>Level:</strong> ND 1</p>
            <p><strong>Semester:</strong> First Semester</p>
            <p><strong>Year:</strong> 2023</p>
            <hr>
            <div class="question-content">
                <h3>Section A: Multiple Choice</h3>
                <ol>
                    <li>What is the output of printf("%d", 10);?
                        <ul>
                            <li>A. 10</li>
                            <li>B. "10"</li>
                            <li>C. 10.0</li>
                            <li>D. None of the above</li>
                        </ul>
                    </li>
                    <li>Which of the following is not a programming language?
                        <ul>
                            <li>A. Python</li>
                            <li>B. Java</li>
                            <li>C. HTML</li>
                            <li>D. CSS</li>
                        </ul>
                    </li>
                </ol>
                
                <h3>Section B: Theory</h3>
                <ol>
                    <li>Explain the concept of object-oriented programming with examples.</li>
                    <li>Differentiate between compiler and interpreter.</li>
                </ol>
            </div>
            <div class="question-actions" style="margin-top: 2rem;">
                <button class="btn btn-primary" onclick="window.print()">Print</button>
            </div>
        `;
        
        modalBody.innerHTML = mockContent;
        modal.style.display = 'block';
    };
});


// Download question with proper error handling
function downloadQuestion(filePath, title) {
    // Show loading state
    const originalText = event.target.innerHTML;
    event.target.innerHTML = 'Downloading... <span class="loading"></span>';
    event.target.disabled = true;
    
    // Create a temporary link to trigger download
    const link = document.createElement('a');
    link.href = filePath;
    link.download = title + '.' + filePath.split('.').pop();
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    // Reset button after a delay
    setTimeout(() => {
        event.target.innerHTML = originalText;
        event.target.disabled = false;
    }, 2000);
}

// Download by question ID using the download handler
function downloadQuestionById(questionId) {
    window.location.href = `../php/download.php?id=${questionId}`;
}

// Enhanced view question with actual content
function viewQuestion(questionId) {
    const modal = document.getElementById('questionModal');
    const modalBody = document.getElementById('modalBody');
    
    // Show loading state
    modalBody.innerHTML = `
        <div class="loading-state">
            <p>Loading question details...</p>
            <div class="loading"></div>
        </div>
    `;
    modal.style.display = 'block';
    
    // Fetch question details (you would need to create this API endpoint)
    fetch(`../php/get_question.php?id=${questionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modalBody.innerHTML = `
                    <h2>${data.question.title}</h2>
                    <div class="question-details">
                        <p><strong>Department:</strong> ${data.question.department_name}</p>
                        <p><strong>Level:</strong> ${data.question.level_name}</p>
                        <p><strong>Semester:</strong> ${data.question.semester_name}</p>
                        <p><strong>Year:</strong> ${data.question.year}</p>
                        ${data.question.description ? `<p><strong>Description:</strong> ${data.question.description}</p>` : ''}
                    </div>
                    <div class="modal-actions">
                        <button class="btn btn-primary" onclick="downloadQuestionById(${questionId})">Download File</button>
                        <button class="btn btn-secondary" onclick="printQuestion(${questionId})">Print</button>
                    </div>
                `;
            } else {
                modalBody.innerHTML = `<p>Error loading question details.</p>`;
            }
        })
        .catch(error => {
            modalBody.innerHTML = `<p>Error loading question details. Please try again.</p>`;
            console.error('Error:', error);
        });
}

// Print functionality
function printQuestion(questionId) {
    // This would open a print-friendly version of the question
    window.open(`../php/print_question.php?id=${questionId}`, '_blank');
}