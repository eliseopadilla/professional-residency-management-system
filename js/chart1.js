const ctx2 = document.getElementById('lineChart').getContext("2d");
const addButton = document.getElementById('addButton');
const taskInput = document.getElementById('taskInput');
const taskList = document.getElementById('taskList');

const doughnut = new Chart(ctx2, {
  type: 'line',
  data: {
    labels: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre','Noviembre','Diciembre'],
    datasets: [{
      label: 'Earnings   in $',
      data: [1900,2017,1910,2019, 2020, 1950, 2022, 2023, 2024, 2025 ],
      
      borderWidth: 1
     
    }]
  },
  options: {
    responsive:true
  }
});

        // Obtener elementos del DOM
       

        // Función para agregar una nueva tarea
        function addTask() {
            const taskText = taskInput.value.trim();
            if (taskText !== "") {
                const li = document.createElement('li');
                li.classList.add('task');

                // Crear el texto de la tarea
                const p = document.createElement('p');
                p.textContent = taskText;

                // Crear el botón para eliminar la tarea
                const removeButton = document.createElement('button');
                removeButton.classList.add('btn-remove');
                removeButton.textContent = 'Eliminar';

                // Agregar el botón de eliminar y el texto de la tarea a la tarea
                li.appendChild(p);
                li.appendChild(removeButton);

                // Agregar la tarea a la lista
                taskList.appendChild(li);

                // Limpiar el input
                taskInput.value = "";

                // Agregar evento para eliminar la tarea
                removeButton.addEventListener('click', function() {
                    taskList.removeChild(li);
                });
            }
        }

        // Evento de click en el botón de agregar tarea
        addButton.addEventListener('click', addTask);

        // Permitir agregar tarea presionando Enter
        taskInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                addTask();
            }
        });


    
   