<template>
  <Layout ref="load" class="bargain-detail bg-f8">
    <Navbar />
    <DetailCellGoodsCard :items="goods_info" :load-data="loadData" />
    <div class="cell-info">
      <div class="text-center">
        现价
        <span class="price">{{detail.now_bargain_money | yuan}}</span>元，还能砍
        <span class="price">{{detail.can_bargain_money | yuan}}</span>元
      </div>
      <InviteBtnGroup :detail="detail" v-if="detail.is_my_bargain" />
      <ShareBtnGroup
        v-model="detail.is_help_bargain"
        :bargain_uid="bargain_uid"
        :bargain_record_id="detail.bargain_record_id"
        :isCanBargain="isCanBargain"
        :isShowBargain="isShowBargain"
        v-else-if="bargain_uid"
        :load-data="loadData"
      />
    </div>
    <DividerUserList :list="detail.help_bargain_list" class="divider-cell-group" />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import DetailCellGoodsCard from "./component/detail/CellGoodsCard";
import DividerUserList from "./component/detail/DividerUserList";
import InviteBtnGroup from "./component/detail/InviteBtnGroup";
import ShareBtnGroup from "./component/detail/ShareBtnGroup";
import { GET_BARGAINDETAIL } from "@/api/bargain";
import { isEmpty, filterUriParams } from "@/utils/util";
export default sfc({
  name: "bargain-detail",
  data() {
    return {
      detail: {}
    };
  },
  computed: {
    // 获取分享链接进来的uid
    bargainuid() {
      return this.$route.params.bargainuid;
    },
    // 自身 砍价 uid
    bargain_uid() {
      return this.detail.bargain_uid ? this.detail.bargain_uid : false;
    },
    isCanBargain() {
      return parseFloat(this.detail.can_bargain_money) <= 0 ? false : true;
    },
    goods_info() {
      const info = {};
      const detail = this.detail;
      info.id = detail.goods_id;
      info.title = detail.goods_name;
      info.picture = detail.pic_cover;
      info.price = detail.start_money;
      info.end_time = detail.end_bargain_time;
      return info;
    },
    isShowBargain() {
      return !this.detail.is_buy;
    }
  },
  watch: {
    bargainuid(id) {
      if (id) {
        this.loadData();
      }
    }
  },
  mounted() {
    if (this.$store.state.config.addons.bargain) {
      this.loadData();
    } else {
      this.$refs.load.fail({ errorText: "未开启砍价应用", showFoot: false });
    }
  },
  methods: {
    loadData() {
      const $this = this;
      const params = {};
      params.goods_id = $this.$route.params.goodsid;
      params.bargain_id = $this.$route.params.bargainid;
      params.bargain_uid = $this.$route.params.bargainuid;
      GET_BARGAINDETAIL(params)
        .then(({ data }) => {
          $this.detail = data;
          $this
            .onShare({
              title: $this.detail.goods_name,
              desc: "快来帮我砍价吧！",
              imgUrl: $this.$BASESRC($this.detail.pic_cover),
              link:
                $this.$store.state.domain +
                "/wap" +
                $this.$route.path +
                filterUriParams($this.$route.query, "extend_code")
            })
            .then(() => {
              $this.$refs.load.success();
            });
        })
        .catch(() => {
          $this.$refs.load.fail();
        });
    }
  },
  components: {
    DetailCellGoodsCard,
    DividerUserList,
    InviteBtnGroup,
    ShareBtnGroup
  }
});
</script>
<style scoped>
.divider-cell-group {
  margin: 10px 0;
}

.cell-info {
  padding: 10px 15px;
  background: #fff;
}

.price {
  color: #ff454e;
}
</style>

