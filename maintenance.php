<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Maintenance</title>

<style>
body {
  margin: 0;
  background: #0a0a0a;
  color: #00ffea;
  font-family: "Courier New", monospace;
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  overflow: hidden;
}

/* Glitch text */
.glitch {
  font-size: 3rem;
  position: relative;
  animation: glitch 1s infinite;
}

@keyframes glitch {
  0% { text-shadow: 2px 0 red, -2px 0 blue; }
  25% { text-shadow: -2px 0 red, 2px 0 blue; }
  50% { text-shadow: 2px 2px red, -2px -2px blue; }
  75% { text-shadow: -2px -2px red, 2px 2px blue; }
  100% { text-shadow: 2px 0 red, -2px 0 blue; }
}

/* Loader */
.loader {
  margin-top: 30px;
  font-size: 1.2rem;
}

.loader span {
  animation: blink 1.5s infinite;
}

.loader span:nth-child(2) { animation-delay: 0.2s; }
.loader span:nth-child(3) { animation-delay: 0.4s; }

@keyframes blink {
  0%, 80%, 100% { opacity: 0; }
  40% { opacity: 1; }
}

/* Background animation */
.bg {
  position: absolute;
  width: 200%;
  height: 200%;
  background: repeating-linear-gradient(
    0deg,
    rgba(0,255,234,0.05),
    rgba(0,255,234,0.05) 1px,
    transparent 1px,
    transparent 3px
  );
  animation: move 10s linear infinite;
}

@keyframes move {
  from { transform: translateY(0); }
  to { transform: translateY(-50%); }
}
</style>

</head>

<body>

<div class="bg"></div>

<div style="text-align:center; z-index:1;">
  <div class="glitch">SYSTEM DOWN</div>
  <p>Server is under maintenance</p>

  <div class="loader">
    Loading<span>.</span><span>.</span><span>.</span>
  </div>
</div>

</body>
</html>