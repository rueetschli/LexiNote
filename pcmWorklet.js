// pcmWorklet.js
class PCMProcessor extends AudioWorkletProcessor {
  process(inputs) {
    const input = inputs[0];
    if (input && input[0]) {
      const channel = input[0];
      // Umwandlung von 32-bit float zu 16-bit integer PCM
      const pcmBuffer = new Int16Array(channel.length);
      for (let i = 0; i < channel.length; i++) {
        let s = Math.max(-1, Math.min(1, channel[i]));
        pcmBuffer[i] = s < 0 ? s * 0x8000 : s * 0x7FFF;
      }
      // Sende den Puffer an den Hauptthread
      this.port.postMessage(pcmBuffer.buffer, [pcmBuffer.buffer]);
    }
    return true;
  }
}
registerProcessor('pcm-worklet', PCMProcessor);