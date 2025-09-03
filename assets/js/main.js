// Smooth scrolling for navigation links
document.addEventListener("DOMContentLoaded", () => {
  // Smooth scrolling
  const navLinks = document.querySelectorAll('.nav a[href^="#"]')
  navLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      e.preventDefault()
      const targetId = this.getAttribute("href")
      const targetSection = document.querySelector(targetId)
      if (targetSection) {
        targetSection.scrollIntoView({
          behavior: "smooth",
          block: "start",
        })
      }
    })
  })

  // Contact form handling
  const contactForm = document.getElementById("contactForm")
  if (contactForm) {
    contactForm.addEventListener("submit", function (e) {
      e.preventDefault()

      const formData = new FormData(this)

      fetch("contact.php", {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            alert("Mensaje enviado correctamente")
            contactForm.reset()
          } else {
            alert(data.message || "Error al enviar el mensaje")
          }
        })
        .catch((error) => {
          console.error("Error:", error)
          alert("Error al enviar el mensaje. Verifica tu conexión e inténtalo de nuevo.")
        })
    })
  }

  // Form validation
  const inputs = document.querySelectorAll("input, textarea")
  inputs.forEach((input) => {
    input.addEventListener("blur", validateField)
    input.addEventListener("input", clearError)
  })
})

function validateField(e) {
  const field = e.target
  const value = field.value.trim()

  // Remove existing error
  clearError(e)

  if (field.hasAttribute("required") && !value) {
    showFieldError(field, "Este campo es obligatorio")
    return false
  }

  if (field.type === "email" && value && !isValidEmail(value)) {
    showFieldError(field, "Ingresa un email válido")
    return false
  }

  return true
}

function clearError(e) {
  const field = e.target
  const errorElement = field.parentNode.querySelector(".field-error")
  if (errorElement) {
    errorElement.remove()
  }
  field.style.borderColor = "#e5e7eb"
}

function showFieldError(field, message) {
  field.style.borderColor = "#dc2626"
  const errorElement = document.createElement("span")
  errorElement.className = "field-error"
  errorElement.style.color = "#dc2626"
  errorElement.style.fontSize = "0.875rem"
  errorElement.style.marginTop = "0.25rem"
  errorElement.style.display = "block"
  errorElement.textContent = message
  field.parentNode.appendChild(errorElement)
}

function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  return emailRegex.test(email)
}

function showAlert(message, type) {
  // Remove existing alerts
  const existingAlerts = document.querySelectorAll(".alert")
  existingAlerts.forEach((alert) => alert.remove())

  const alert = document.createElement("div")
  alert.className = `alert alert-${type}`
  alert.textContent = message

  const contactForm = document.getElementById("contactForm")
  if (contactForm) {
    contactForm.parentNode.insertBefore(alert, contactForm)

    // Auto remove after 5 seconds
    setTimeout(() => {
      alert.remove()
    }, 5000)
  }
}

// Admin functionality
function confirmDelete(message) {
  return confirm(message || "¿Estás seguro de que quieres eliminar este elemento?")
}

// Image preview for file uploads
function previewImage(input, previewId) {
  if (input.files && input.files[0]) {
    const reader = new FileReader()
    reader.onload = (e) => {
      const preview = document.getElementById(previewId)
      if (preview) {
        preview.src = e.target.result
        preview.style.display = "block"
      }
    }
    reader.readAsDataURL(input.files[0])
  }
}

function validateContactForm() {
  const name = document.getElementById("name").value.trim()
  const email = document.getElementById("email").value.trim()
  const message = document.getElementById("message").value.trim()

  let isValid = true

  // Clear previous errors
  document.querySelectorAll(".field-error").forEach((error) => error.remove())
  document.querySelectorAll("input, textarea").forEach((field) => {
    field.style.borderColor = "#e5e7eb"
  })

  // Name validation
  if (!name) {
    showFieldError(document.getElementById("name"), "El nombre es obligatorio")
    isValid = false
  } else if (name.length < 2) {
    showFieldError(document.getElementById("name"), "El nombre debe tener al menos 2 caracteres")
    isValid = false
  } else if (!/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/.test(name)) {
    showFieldError(document.getElementById("name"), "El nombre solo puede contener letras y espacios")
    isValid = false
  }

  // Email validation
  if (!email) {
    showFieldError(document.getElementById("email"), "El email es obligatorio")
    isValid = false
  } else if (!isValidEmail(email)) {
    showFieldError(document.getElementById("email"), "Ingresa un email válido")
    isValid = false
  }

  // Message validation
  if (!message) {
    showFieldError(document.getElementById("message"), "El mensaje es obligatorio")
    isValid = false
  } else if (message.length < 10) {
    showFieldError(document.getElementById("message"), "El mensaje debe tener al menos 10 caracteres")
    isValid = false
  } else if (message.length > 1000) {
    showFieldError(document.getElementById("message"), "El mensaje no puede exceder 1000 caracteres")
    isValid = false
  }

  return isValid
}

// Añade este código al principio del archivo
document.addEventListener('DOMContentLoaded', function() {
    console.log('Verificando carga de proyectos...');
    const projectsGrid = document.querySelector('.projects-grid');
    if (projectsGrid) {
        console.log('Número de proyectos cargados:', projectsGrid.children.length);
        Array.from(projectsGrid.children).forEach((project, index) => {
            console.log(`Proyecto ${index + 1}:`, {
                título: project.querySelector('.project-title')?.textContent,
                imagen: project.querySelector('.project-img')?.src
            });
        });
    }
});
