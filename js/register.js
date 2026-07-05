document.addEventListener('DOMContentLoaded', function() {
    // Load departments
    fetchDepartments();
    
    // Form validation
    document.getElementById('registerForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const matricNumber = document.getElementById('matric_number').value;
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        // Validate matric number format
        if (!validateMatricNumber(matricNumber)) {
            alert('Invalid matric number format. Please use format like CE2024010001');
            return;
        }
        
        // Check if passwords match
        if (password !== confirmPassword) {
            alert('Passwords do not match');
            return;
        }
        
        // If validation passes, submit the form
        this.submit();
    });
    
    function validateMatricNumber(matric) {
        // Format: CE2024010001
        const regex = /^[A-Z]{2}\d{4}(01|02)\d{4}$/;
        return regex.test(matric);
    }
    
    function fetchDepartments() {
        // In a real application, this would be an API call
        const departments = [
            { id: 1, name: 'Accountancy', code: 'AC' },
            { id: 2, name: 'Agricultural Technology', code: 'AG' },
            { id: 3, name: 'Business Administration and management', code: 'BA' },
            { id: 4, name: 'Computer Engineering', code: 'CE' },
            { id: 5, name: 'Computer Science', code: 'CS' },
            { id: 6, name: 'Electrical and Eletonics Engineering Technology', code: 'EE' },
            { id: 7, name: 'Estate Management', code: 'EM' },
            { id: 8, name: 'Office Technology and Management', code: 'OT' },
            { id: 9, name: 'Public Administration',  code: 'PA' },
            { id: 10, name: 'Science Laboratory Technology',  code: 'SL' },
            { id: 11, name: 'Statistics',  code: 'ST' },
            { id: 12, name: 'Tourism and Leisure Management', code: 'TM' },
            { id: 13, name: 'Urban and Regional Planning',  code: 'UR' }
        ];
        
        const departmentSelect = document.getElementById('department');
        departments.forEach(dept => {
            const option = document.createElement('option');
            option.value = dept.id;
            option.textContent = dept.name;
            departmentSelect.appendChild(option);
        });
    }
});