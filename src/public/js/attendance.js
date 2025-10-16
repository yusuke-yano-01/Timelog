function updateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString("ja-JP", {
        hour: "2-digit",
        minute: "2-digit",
    });
    document.getElementById("current-time").textContent = timeString;
}

setInterval(updateTime, 1000);
updateTime();
