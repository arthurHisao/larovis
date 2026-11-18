export function playBellSound() {
  try {
    const audioCtx = new (
      window.AudioContext || (window as any).webkitAudioContext
    )();
    const now = audioCtx.currentTime;

    // As duas notas do "ding, ding" (frequências em Hz: C6 e E6)
    const notes = [1046.5, 1318.5];

    notes.forEach((freq, index) => {
      // Cria um pequeno atraso (0.12s) entre o primeiro e o segundo "ding"
      const startTime = now + index * 0.12;

      const osc = audioCtx.createOscillator();
      const gainNode = audioCtx.createGain();

      osc.type = "sine"; // Onda limpa e suave
      osc.frequency.setValueAtTime(freq, startTime);

      // Envelope: volume moderado para não estourar e decaimento mágico suave
      gainNode.gain.setValueAtTime(0.001, startTime);
      gainNode.gain.linearRampToValueAtTime(0.15, startTime + 0.02); // Ataque rápido
      gainNode.gain.exponentialRampToValueAtTime(
        0.00001,
        startTime + 0.7, // Ecoando levemente por 0.7 segundos
      );

      osc.connect(gainNode);
      gainNode.connect(audioCtx.destination);

      osc.start(startTime);
      osc.stop(startTime + 0.7);
    });
  } catch (error) {
    console.log("Áudio bloqueado pelo browser até haver interação:", error);
  }
}
