<template>
  <div>
    <van-cell-group>
      <van-cell title="兑换类型" class="cell-panel">
        <van-radio-group v-model="params.exchange_type" class="cell-radio-group" @change="exchange">
          <van-radio :name="1">{{pointText}}换{{typeToUpperCase}}</van-radio>
          <van-radio :name="2">{{typeToUpperCase}}换{{pointText}}</van-radio>
        </van-radio-group>
      </van-cell>
      <template v-if="params.exchange_type == 1">
        <van-cell :title="'可用'+pointText" class="cell-panel">
          <span class="text-maintone">{{$store.state.member.info.point}}</span>
          <span class="fs-12 text-secondary">(最低兑换{{info.lowPoint}}{{pointText}})</span>
        </van-cell>
        <van-field
          :label="pointText"
          type="number"
          :placeholder="'请输入兑换的'+pointText"
          v-model.number="params.point"
          @keydown="pointKeydown"
          @blur="countExport"
        />
        <van-field :label="typeToUpperCase" disabled :value="countExportText" />
      </template>
      <template v-if="params.exchange_type == 2">
        <van-cell :title="'可用'+typeToUpperCase" class="cell-panel">
          <span class="text-maintone">{{info.balance}}</span>
        </van-cell>
        <van-field
          :label="typeToUpperCase"
          type="number"
          :placeholder="'请输入兑换的'+typeToUpperCase"
          @keydown="biKeydown"
          v-model="params[type]"
          @blur="countExport"
        />
        <van-field :label="pointText" disabled :value="countExportText" />
      </template>
    </van-cell-group>
    <PoundageGroup :type="type" :data="poundage" @change="slider" v-if="type == 'eth'" />
  </div>
</template>

<script>
import { handleInput, handleInt } from "@/utils/util";
import PoundageGroup from "./PoundageGroup";
import { COUNT_BLOCKCHAINEXPORT } from "@/api/blockchain";
export default {
  data() {
    return {
      typeToUpperCase: this.type.toUpperCase(),
      countExportText: "--"
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
    biKeydown(e) {
      handleInput(e, this.type == "eth" ? 6 : 4);
    },
    pointKeydown(e) {
      handleInt(e);
    },
    countExport(e) {
      let params = {};
      params.num = e.target.value;
      params.coin_type = this.type == "eth" ? 1 : 2;
      params.exchange_type = this.params.exchange_type;
      if (e.target.value) {
        this.getExportNum(params);
      } else {
        this.countExportText = "--";
      }
    },
    getExportNum(params) {
      this.countExportText = "计算中...";
      COUNT_BLOCKCHAINEXPORT(params)
        .then(({ data }) => {
          this.countExportText = data.number;
        })
        .catch(() => {
          this.countExportText = "计算失败";
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

