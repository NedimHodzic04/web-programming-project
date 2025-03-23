document.addEventListener("DOMContentLoaded", function () {
    const editProfileBtn = document.querySelector("#editProfileModal .btn-primary");
    const profileSection = document.querySelector("#profile");

    // Profile fields
    const nameField = document.querySelector("#editName");
    const emailField = document.querySelector("#editEmail");
    const cityField = document.querySelector("#editCity");
    const addressField = document.querySelector("#editAddress");
    const zipField = document.querySelector("#editZip");

    // Load profile from Local Storage or set defaults
    let userProfile = JSON.parse(localStorage.getItem("userProfile")) || {
        name: "Nedim Hodzic",
        email: "johndoe@example.com",
        city: "New York",
        address: "123 Main St, Apt 4B",
        zip: "10001"
    };

    function updateProfileUI() {
        profileSection.querySelector("p:nth-child(1)").innerHTML = `<strong>Name:</strong> ${userProfile.name}`;
        profileSection.querySelector("p:nth-child(2)").innerHTML = `<strong>Email:</strong> ${userProfile.email}`;
        profileSection.querySelector("p:nth-child(3)").innerHTML = `<strong>City:</strong> ${userProfile.city}`;
        profileSection.querySelector("p:nth-child(4)").innerHTML = `<strong>Address:</strong> ${userProfile.address}`;
        profileSection.querySelector("p:nth-child(5)").innerHTML = `<strong>Zip Code:</strong> ${userProfile.zip}`;
    }

    function loadProfile() {
        // Populate the profile section
        updateProfileUI();

        // Populate the modal form
        nameField.value = userProfile.name;
        emailField.value = userProfile.email;
        cityField.value = userProfile.city;
        addressField.value = userProfile.address;
        zipField.value = userProfile.zip;
    }

    editProfileBtn.addEventListener("click", function () {
        // Save changes
        userProfile.name = nameField.value;
        userProfile.email = emailField.value;
        userProfile.city = cityField.value;
        userProfile.address = addressField.value;
        userProfile.zip = zipField.value;

        // Store in local storage
        localStorage.setItem("userProfile", JSON.stringify(userProfile));

        // Update UI
        updateProfileUI();

        // Close modal
        document.querySelector("#editProfileModal .btn-close").click();
    });

    // Load profile on page load
    loadProfile();

    // Trigger profile loading when the modal is shown
    const modal = document.getElementById('editProfileModal');
    modal.addEventListener('show.bs.modal', function () {
        loadProfile();
    });
});
