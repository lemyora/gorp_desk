<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Disciplina - Expense Tracker & Journal</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background-color: #0d1117;
      color: white;
    }
    header {
      background-color: #161b22;
      padding: 10px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid #30363d;
    }
    header h1 {
      margin: 0;
      font-size: 1.5em;
    }
    nav a {
      color: white;
      margin-left: 15px;
      text-decoration: none;
    }
    .container {
      padding: 20px;
    }
    .card {
      background-color: #161b22;
      border: 1px solid #30363d;
      border-radius: 10px;
      padding: 20px;
      margin-bottom: 20px;
    }
    .form-control {
      padding: 10px;
      margin: 5px;
      border-radius: 5px;
      border: none;
    }
    .btn {
      padding: 10px 15px;
      background-color: #238636;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
    }
    canvas {
      background-color: white;
      border-radius: 8px;
    }
  </style>
</head>
<body>
  <header>
    <h1>⚡ Disciplina</h1>
    <nav>
      <a href="#expenses">Expense Tracker</a>
      <a href="#journal">Journal</a>
    </nav>
  </header>

  <div class="container">
    <div class="card" id="expenses">
      <h2>Expenses for <span id="monthLabel"></span></h2>
      <canvas id="expenseChart" height="100"></canvas>

      <h3 style="margin-top:20px;">Add Transaction</h3>
      <input type="text" id="expenseName" class="form-control" placeholder="Expense Name">
      <input type="number" id="expenseAmount" class="form-control" placeholder="Amount">
      <button class="btn" onclick="addExpense()">Add</button>

      <ul id="expenseList"></ul>
    </div>

    <div class="card" id="journal">
      <h2>Daily Journal</h2>
      <textarea id="journalText" class="form-control" rows="5" placeholder="What did you learn today? How did you improve?"></textarea>
      <button class="btn" onclick="saveJournal()">Save Entry</button>
      <div id="journalHistory"></div>
    </div>
  </div>

  <script>
    const expenses = JSON.parse(localStorage.getItem("expenses") || "[]");
    const journals = JSON.parse(localStorage.getItem("journals") || "{}");
    const today = new Date().toISOString().split('T')[0];

    document.getElementById("monthLabel").textContent = new Date().toLocaleString('default', { month: 'long', year: 'numeric' });

    function renderChart() {
      const ctx = document.getElementById('expenseChart').getContext('2d');
      const dailyTotals = Array(31).fill(0);
      expenses.forEach(e => {
        const day = new Date(e.date).getDate();
        dailyTotals[day - 1] += e.amount;
      });
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: [...Array(31).keys()].map(i => i + 1),
          datasets: [{
            label: 'Daily Expenses',
            data: dailyTotals,
            borderColor: '#58a6ff',
            backgroundColor: '#1f6feb40',
            tension: 0.3,
            fill: true
          }]
        },
        options: {
          plugins: { legend: { labels: { color: 'black' } } },
          scales: {
            x: { ticks: { color: 'black' } },
            y: { ticks: { color: 'black' } }
          }
        }
      });
    }

    function addExpense() {
      const name = document.getElementById("expenseName").value;
      const amount = parseFloat(document.getElementById("expenseAmount").value);
      if (!name || !amount) return alert("Please fill all fields");
      expenses.push({ name, amount, date: new Date().toISOString() });
      localStorage.setItem("expenses", JSON.stringify(expenses));
      location.reload();
    }

    function renderExpenses() {
      const list = document.getElementById("expenseList");
      list.innerHTML = '';
      expenses.slice(-10).reverse().forEach(e => {
        const li = document.createElement("li");
        li.textContent = `${e.name} - ₹${e.amount} (${new Date(e.date).toLocaleDateString()})`;
        list.appendChild(li);
      });
    }

    function saveJournal() {
      const text = document.getElementById("journalText").value;
      if (!text) return alert("Journal entry cannot be empty");
      journals[today] = text;
      localStorage.setItem("journals", JSON.stringify(journals));
      location.reload();
    }

    function renderJournal() {
      const history = document.getElementById("journalHistory");
      history.innerHTML = '<h4>Past Entries:</h4>';
      for (let date in journals) {
        const p = document.createElement("p");
        p.innerHTML = `<strong>${date}</strong>: ${journals[date]}`;
        history.appendChild(p);
      }
    }

    renderChart();
    renderExpenses();
    renderJournal();
  </script>
</body>
</html>
