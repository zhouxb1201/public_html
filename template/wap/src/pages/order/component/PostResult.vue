<template>
  <div class="result-box">
    <ResultBox :state-type="stateType">
      <van-row type="flex" justify="center" class="result-info">
        <div class="text">
          <div>{{resultInfo.message}}</div>
          <div class="reason" v-if="resultInfo.result === 'fail'">原因：{{resultInfo.reason}}</div>
        </div>
      </van-row>
    </ResultBox>
    <div class="foot-btn-group" v-if="resultInfo.result === 'fail' && resultInfo.isAgainApply">
      <van-button size="normal" block round type="danger" @click="onAgainApply">重新申请</van-button>
    </div>
  </div>
</template>

<script>
import ResultBox from "@/components/ResultBox";
export default {
  data() {
    return {};
  },
  props: {
    resultInfo: {
      type: [Object, Boolean]
    }
  },
  computed: {
    stateType() {
      return "refund-" + this.resultInfo.result;
    }
  },
  methods: {
    onAgainApply() {
      this.$emit("change-page-type", 1);
    }
  },
  components: {
    ResultBox
  }
};
</script>

<style scoped>
.result-box {
  height: calc(100% - 46px);
}

.result-info {
  display: flex;
  align-items: center;
  min-height: 100px;
  margin: 0 auto;
  background: #fff;
  padding: 20px 30px;
}

.result-info .text {
  text-align: center;
}
.result-info .reason {
  font-size: 12px;
  color: #909399;
  line-height: 1.4;
  margin-top: 10px;
}
</style>
