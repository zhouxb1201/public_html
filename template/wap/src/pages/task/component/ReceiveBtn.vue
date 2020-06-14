<template>
  <van-button
    type="danger"
    round
    hairline
    size="small"
    class="btn"
    :disabled="isDisabled"
    :loading="isLoading"
    @click="bindMobile('receive')"
    v-if="state == 0"
  >领取</van-button>
</template>

<script>
import { RECEIVE_TASK } from "@/api/task";
import { bindMobile } from "@/mixins";
export default {
  data() {
    return {
      isDisabled: false,
      isLoading: false
    };
  },
  props: {
    state: [String, Number],
    id: [String, Number]
  },
  mixins: [bindMobile],
  methods: {
    receive() {
      const id = this.id;
      this.isLoading = true;
      RECEIVE_TASK(id)
        .then(({ message }) => {
          this.isDisabled = true;
          this.$Toast.success(message);
          this.isLoading = false;
        })
        .catch(() => {
          this.isLoading = false;
        });
    }
  }
};
</script>
