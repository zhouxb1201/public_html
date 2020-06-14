<template>
  <Layout ref="load" class="statistic bg-f8">
    <Navbar/>
    <van-cell :border="false" class="card-group-box">
      <div class="text-center">
        <div>{{shop_name}}{{'（'+store_name+'）'}}</div>
        <div>
          <span class="e-handle" @click="dateShow = true">{{date}}</span>
        </div>
      </div>
    </van-cell>
    <van-cell-group class="card-group-box">
      <van-cell title="总销售额" value-class="text-maintone" :value="detail.sale_money | yuan"/>
      <van-cell title="交易笔数" value-class="text-maintone" :value="detail.sale_count | bi"/>
    </van-cell-group>
    <van-cell-group class="card-group-box">
      <van-cell title="支付宝" value-class="text-maintone" :value="detail.sale_money_alipay | yuan"/>
      <van-cell title="微信" value-class="text-maintone" :value="detail.sale_money_wechat | yuan"/>
      <van-cell title="余额" value-class="text-maintone" :value="detail.sale_money_balance | yuan"/>
    </van-cell-group>
    <van-cell-group class="card-group-box">
      <van-cell title="核销订单" value-class="text-maintone" :value="detail.finished_count | bi"/>
      <van-cell title="待核销订单" value-class="text-maintone" :value="detail.unfinished_count | bi"/>
    </van-cell-group>
    <PopupDate v-model="dateShow" @confirm="onDate"/>
  </Layout>
</template>

<script>
import { GET_STATISTICDATA } from "@/api/statistic";
import { formatDate } from "@/utils/util";
import PopupDate from "./component/PopupDate";
export default {
  name: "statistic",
  data() {
    return {
      store_name: null,
      shop_name: null,
      date: formatDate(new Date().getTime()),
      detail: {},

      dateShow: false
    };
  },
  filters: {
    bi(value) {
      return value + "笔";
    }
  },
  created() {
    this.loadData();
  },
  methods: {
    onDate(date) {
      this.date = formatDate(date);
      this.loadData();
    },
    loadData() {
      const $this = this;
      $this.$store
        .dispatch("getAccountInfo")
        .then(info => {
          $this.store_name = info.store_info.store_name;
          $this.shop_name = info.shop_name;
          GET_STATISTICDATA($this.date)
            .then(({ data }) => {
              $this.detail = data;
              $this.$refs.load.success();
            })
            .catch(() => {
              $this.$refs.load.fail();
            });
        })
        .catch(() => {
          $this.$refs.load.fail();
        });
    }
  },
  components: {
    PopupDate
  }
};
</script>

<style scoped>

</style>
