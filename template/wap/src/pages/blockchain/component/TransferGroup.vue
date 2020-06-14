<template>
  <div>
    <van-cell-group>
      <van-cell :title="'可用'+typeToUpperCase" class="cell-panel">
        <span class="text-maintone">{{info.balance}}</span>
      </van-cell>
      <van-field
        :label="'收款'+(type=='eth'?'地址':'账号')"
        :placeholder="'请输入收款人'+(type=='eth'?'钱包地址':'账号')"
        clearable
        v-model="params[info.tokey]"
        type="textarea"
        rows="1"
        :autosize="{maxHeight:48}"
        :right-icon="$store.state.isWeixin&&$store.getters.config.is_wchat?'v-icon-qr1':''"
        @blur="blur"
        @click-right-icon="scanQR"
      />
      <van-field
        :label="'转账'+typeToUpperCase"
        type="number"
        :placeholder="'请输入转账的'+typeToUpperCase"
        @keydown="keydown"
        v-model="params[type]"
      />
      <van-field
        label="备注"
        type="textarea"
        rows="1"
        clearable
        :autosize="{maxHeight:48}"
        v-model="params.memo"
        placeholder="选填"
      />
    </van-cell-group>
    <PoundageGroup :type="type" :data="poundage" @change="slider" v-if="type == 'eth'" />
  </div>
</template>

<script>
import PoundageGroup from "./PoundageGroup";
import { handleInput } from "@/utils/util";
export default {
  data() {
    return {
      typeToUpperCase: this.type.toUpperCase()
    };
  },
  props: {
    type: String,
    info: Object,
    params: Object,
    poundage: Object
  },
  computed: {
    pointText() {
      return this.$store.state.member.memberSetText.point_style;
    }
  },
  methods: {
    exchange(e) {
      this.$emit("ex-change", e);
    },
    slider(e) {
      this.$emit("sd-change", e);
    },
    keydown(e) {
      handleInput(e, this.type == "eth" ? 6 : 4);
    },
    blur(e) {
      this.$emit("address-blur", e.target.value);
    },
    scanQR() {
      this.$store.dispatch("wxScanQRCode").then(res => {
        this.$emit("scanqr", res);
      });
    }
  },
  components: {
    PoundageGroup
  }
};
</script>

<style scoped>
</style>

