<template>
  <Layout ref="load" class="microshop-centre bg-f8">
    <Navbar :title="navbarTitle" />
    <div v-if="!order_type">
      <Mine v-if="info.isshopkeeper" :shopkeeperInfo="info" :incomelist="incomelist"></Mine>
      <ApplyPost page-type="2" :info="info" :goods="goodsArr" v-else />
    </div>
    <div v-else>
      <ApplyPost
        :page-type="order_type"
        :info="info"
        :goods="goodsArr"
        v-if="order_type && info.isshopkeeper"
      />
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import ApplyPost from "./component/Post";
import Mine from "./component/Mine";
export default sfc({
  name: "microshop-centre",
  data() {
    return {
      info: {}, //店主信息
      goodsArr: [],
      incomelist: {} //收益
    };
  },
  mounted() {
    if (this.$store.state.config.addons.microshop) {
      this.loadData();
    } else {
      this.$refs.load.fail({ errorText: "未开启微店应用", showFoot: false });
    }
  },
  computed: {
    navbarTitle() {
      let title = "";
      if (this.info.isshopkeeper) {
        title = "我的微店";
      } else {
        title = "微店中心";
      }
      if (title) document.title = title;
      return title;
    },
    order_type() {
      let order_type = this.$route.query.order_type;
      if (order_type && order_type > 2) {
        return order_type;
      }
    }
  },
  methods: {
    loadData() {
      const $this = this;
      $this.$store
        .dispatch("getMicroshopInfo")
        .then(data => {
          $this.info = $this.$store.state.microshop.info;
          if ($this.info.isdistributor == 2 || $this.info.microshop_set.shopKeeper_check == "1") {
            //判断是否为分销商
            $this.goodsArr = data.microshop_set.goods_info || [];
            $this.incomelist = {
              profit: data.profit,
              withdrawals: data.withdrawals,
              total_profit: data.total_profit
            };
            $this.$refs.load.success();
          } else {
            $this.$router.push({ name: "commission-apply" });
          }
        })
        .catch(() => {
          $this.$refs.load.fail();
        });
    }
  },
  components: {
    ApplyPost,
    Mine
  }
});
</script>
