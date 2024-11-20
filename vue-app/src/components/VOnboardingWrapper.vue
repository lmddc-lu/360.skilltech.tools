<script setup>
  import { defineComponent, ref, onMounted } from 'vue'
  import { VOnboardingWrapper, VOnboardingStep, useVOnboarding } from 'v-onboarding'
  import 'v-onboarding/dist/style.css'

  const wrapper = ref(null)
  const { start, goToStep, finish } = useVOnboarding(wrapper)
  const props = defineProps(['steps', 'labelFinish', 'labelPrevious', 'labelNext'])
  const steps = props.steps;
  const options = {
    overlay: {
      padding: 20,
      borderRadius: 15
    },
    popper: {modifiers: [
      {name: 'offset', options: {offset: [0,30]}}
    ]}
  };
  onMounted(() => {
    start();
  });

  const emit = defineEmits(['exit', 'finish']);

  const isButtonVisible = {};
  isButtonVisible.exit = true;
  isButtonVisible.previous = true;
  isButtonVisible.next = true;

  const buttonLabels = {};
  buttonLabels.finish = props.labelFinish ? props.labelFinish : "Finish";
  buttonLabels.previous = props.labelPrevious ? props.labelPrevious : "Previous";
  buttonLabels.next = props.labelNext ? props.labelNext : "Next";

  function exit() {
    console.log("exit");
  }

  async function handleExit(){
    await finish();
    emit('exit');
  }

  async function handleFinish(){
    await finish();
    emit('finish');
  }
</script>

<template>
  <VOnboardingWrapper ref="wrapper" :steps="steps" :options="options">
    <template #default="{ previous, next, step, exit, isFirst, isLast, index }">
      <VOnboardingStep>
        <div :class="'v-onboarding-item step-' + (index+1)">
          <div class="v-onboarding-item__header">
            <span
              v-if="step.content.title"
              class="v-onboarding-item__header-title"
            ><span class="step">{{index+1}}/{{steps.length}}</span>{{ step.content.title }}</span>
            <button v-if="isButtonVisible.exit" @click="handleExit" aria-label="Close" class="v-onboarding-item__header-close">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </button>
          </div>
          <p
            v-if="step.content.description && step.content.html"
            class="v-onboarding-item__description"
            v-html="step.content.description"
          />
          <p
            v-else-if="step.content.description"
            class="v-onboarding-item__description"
          >{{ step.content.description }}</p>
          <div class="v-onboarding-item__actions">
            <button
              v-if="!isFirst && isButtonVisible.previous"
              type="button"
              @click="previous"
              class="v-onboarding-btn-secondary"
            >{{ buttonLabels.previous }}</button>
            <button v-if="isButtonVisible.next"
              @click="() => isLast ? handleFinish() : next()"
              type="button"
              class="v-onboarding-btn-primary"
            >{{ isLast ? buttonLabels.finish : buttonLabels.next }}</button>
          </div>
        </div>
      </VOnboardingStep>
    </template>
  </VOnboardingWrapper>
</template>

<style>
:root {
  --v-onboarding-step-arrow-background: #f1bb45;
  /*--v-onboarding-step-arrow-size: 20px;*/
  --v-onboarding-overlay-opacity: 0.3;
  --v-onboarding-step-z: 1001;
}

.v-onboarding-item + div{
  z-index: -1;
}
</style>

