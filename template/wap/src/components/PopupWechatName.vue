<template>
  <van-popup v-model="show" position="bottom" :close-on-click-overlay="false">
    <div class="cert-sel">
      <h3>{{title}}</h3>
      <div class="contain">
        <input type="text" placeholder="请输入您的微信号" class="input" v-model="val" />
        <p class="tips">微信号保存后无法更改，请谨慎填写</p>
      </div>
      <div class="btn-wrap">
        <van-button type="default" size="normal" @click="onCancel">取消</van-button>
        <van-button type="danger" size="normal" @click="onSure">确定</van-button>
      </div>
    </div>
  </van-popup>
</template>

<script>
export default {
  data() {
    return {
      val: ""
    };
  },
  props: {
    title: {
      type: String,
      default: "由于证书生成需要，请完善微信号"
    },
    show: {
      type: Boolean,
      default: false
    }
  },
  mounted() {},
  methods: {
    onCancel() {
      this.$emit("cancel");
    },
    onSure() {
      this.$emit("sure", this.val);
    }
  }
};
</script>

<style  scoped>
.cert-sel {
  min-height: 300px;
}
.contain {
  margin: 0px 20px;
}
.cert-sel h3 {
  text-align: center;
  font-size: 16px;
  font-weight: normal;
  padding-bottom: 20px;
  position: relative;
  margin-bottom: 30px;
  padding-top: 20px;
}
.cert-sel h3::after {
  content: "";
  position: absolute;
  left: 0;
  bottom: -1px;
  height: 1px;
  width: 100%;
  background-color: #ebebeb;
  transform: scaleY(0.5);
}
.input {
  border: 1px solid #ebebeb;
  border-radius: 5px;
  height: 40px;
  line-height: 40px;
  font-size: 14px;
  text-indent: 6px;
  width: 100%;
  display: block;
  box-shadow: 0px 0px 3px #ebebeb;
}
.tips {
  padding-top: 10px;
  color: red;
  font-size: 12px;
}
.btn-wrap {
  display: flex;
  width: 100%;
  position: fixed;
  bottom: 0;
}
.btn-wrap >>> .van-button {
  flex: 1;
  border: none !important;
  border-radius: 0px !important;
}
.btn-wrap >>> .van-button--default:after {
  content: "";
  position: absolute;
  left: 0;
  top: 0;
  height: 1px;
  width: 100%;
  background-color: #ebedf0;
}
</style>

