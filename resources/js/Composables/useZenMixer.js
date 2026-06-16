import { reactive } from 'vue';
import { Howl } from 'howler';

const bancoSonidos = {
  'Tormenta': '/audios/storm',
  'Agua': '/audios/water',
  'Fogata': '/audios/campfire',
  'Lluvia': '/audios/rain',
  'Olas del Mar': '/audios/waves',
  'Aire': '/audios/air',
  'Viento': '/audios/wind',
  'Bosque': '/audios/forest',
  'Lluvia Bosque': '/audios/forest-rain',
  'Ambiente de Fondo': '/audios/background-music',
  'Cafetería': '/audios/cafe',
  'Tráfico': '/audios/city-traffic',
  'Teclado': '/audios/keyboard',
  'Meditación del Tigre': '/audios/meditative-tiger',
  'Ruido Blanco': '/audios/white-noise',
  'Ruido Rosa': '/audios/pink-noise',
  'Ruido Marrón': '/audios/brown-noise',
  'Tibetanos': '/audios/tibetan-meditation'
};

const mixerState = reactive({});
for (const key in bancoSonidos) {
  mixerState[key] = { active: false, volume: 0.5 };
}

const howlerInstances = {};

const getAssetUrl = (path) => {
  const cleanPath = path.startsWith('/') ? path.substring(1) : path;
  const pathname = window.location.pathname;
  const publicIndex = pathname.indexOf('/public');
  if (publicIndex !== -1) {
    const basePath = pathname.substring(0, publicIndex + 7);
    return `${window.location.origin}${basePath}/${cleanPath}`;
  }
  return `${window.location.origin}/${cleanPath}`;
};

export function useZenMixer() {
  const isSoundLocked = (key, isGuest) => {
    if (!isGuest) return false;
    return !['Lluvia', 'Cafetería', 'Viento'].includes(key);
  };

  const toggleMixerSound = (soundKey, isGuest) => {
    if (isSoundLocked(soundKey, isGuest)) {
      return 'locked';
    }
    const state = mixerState[soundKey];
    state.active = !state.active;

    if (state.active) {
      playIndividualSound(soundKey);
    } else {
      pauseIndividualSound(soundKey);
    }
    return 'toggled';
  };

  const playIndividualSound = (soundKey) => {
    const state = mixerState[soundKey];
    if (!state.active) return;

    if (!howlerInstances[soundKey]) {
      const soundPath = bancoSonidos[soundKey];
      howlerInstances[soundKey] = new Howl({
        src: [getAssetUrl(`${soundPath}.webm`), getAssetUrl(`${soundPath}.mp3`)],
        html5: true,
        loop: true,
        volume: state.volume,
        onplayerror: function() {
          if (howlerInstances[soundKey]) {
            howlerInstances[soundKey].once('unlock', function() {
              howlerInstances[soundKey].play();
            });
          }
        }
      });
    }
    howlerInstances[soundKey].play();
  };

  const pauseIndividualSound = (soundKey) => {
    if (howlerInstances[soundKey]) {
      howlerInstances[soundKey].pause();
    }
  };

  const updateMixerVolume = (soundKey) => {
    if (howlerInstances[soundKey]) {
      howlerInstances[soundKey].volume(mixerState[soundKey].volume);
    }
  };

  const playActiveSounds = () => {
    for (const key in mixerState) {
      if (mixerState[key].active) {
        playIndividualSound(key);
      }
    }
  };

  const pauseActiveSounds = () => {
    for (const key in mixerState) {
      if (mixerState[key].active && howlerInstances[key]) {
        howlerInstances[key].pause();
      }
    }
  };

  const stopAllSounds = () => {
    for (const key in howlerInstances) {
      if (howlerInstances[key]) {
        howlerInstances[key].stop();
      }
    }
  };

  return {
    mixerState,
    bancoSonidos,
    toggleMixerSound,
    updateMixerVolume,
    playActiveSounds,
    pauseActiveSounds,
    stopAllSounds,
    isSoundLocked
  };
}
