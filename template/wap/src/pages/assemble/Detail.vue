<template>
  <Layout ref="load" class="assemble-detail bg-f8">
    <Navbar />
    <ResultState message="拼团已结束" v-if="status == -1" />
    <div v-else>
      <van-cell>
        <GoodsCard
          class="goods-card"
          :title="goods.goods_name"
          :thumb="goods.pic_cover | BASESRC"
          :id="goods.goods_id"
        >
          <div class="van-card__price-group" slot="tags">
            <div class="van-card__price">{{goods.group_price | yuan}}</div>
            <div class="van-card__origin-price">{{goods.price | yuan}}</div>
          </div>
          <div slot="bottom" class="foot">
            <CountDown
              :time="finish_time"
              done-text="00:00:00"
              v-if="status !== 1"
              @callback="onCallback"
            >
              <div class="time-end">
                <span>{%d}</span>
                <i>:</i>
                <span>{%h}</span>
                <i>:</i>
                <span>{%m}</span>
                <i>:</i>
                <span>{%s}</span>
              </div>
            </CountDown>
            <div v-if="status !== 1">拼团将失效</div>
            <div class="success-text" v-else>拼单成功</div>
          </div>
        </GoodsCard>
      </van-cell>
      <Divider title="成功拼团好友">
        <div slot="html" class="info">
          <AvatarGroup class="avatar-group" :list="buyer_list" :group_num="detail.group_num" />
          <div v-if="status !== 1">
            <div class="tips">
              再邀请
              <span class="num">{{diff_num}}</span>位朋友下单即可开团成功
            </div>
            <div class="btn-group">
              <van-button
                size="normal"
                block
                round
                type="danger"
                class="btn"
                v-if="!detail.self_order_id"
                @click="onJoin"
              >参与拼团</van-button>
              <van-button
                size="normal"
                block
                round
                type="warning"
                class="btn"
                @click="onInvite"
              >邀请朋友</van-button>
            </div>
          </div>
          <div class="text-center see-order" v-if="detail.self_order_id">
            <router-link :to="'/order/detail/'+detail.self_order_id">查看订单详情 ></router-link>
          </div>
        </div>
      </Divider>
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import CountDown from "@/components/CountDown";
import GoodsCard from "@/components/GoodsCard";
import Divider from "@/components/Divider";
import ResultState from "@/components/ResultState";
import AvatarGroup from "./component/AvatarGroup";
import { isEmpty, filterUriParams } from "@/utils/util";
export default sfc({
  name: "assemble-detail",
  data() {
    return {
      detail: {}
    };
  },
  computed: {
    goods() {
      return this.detail.goods ? this.detail.goods : {};
    },
    finish_time() {
      return this.detail.finish_time ? this.detail.finish_time * 1000 : 0;
    },
    buyer_list() {
      return this.detail.buyer_list ? this.detail.buyer_list : [];
    },
    status() {
      return this.detail.status == undefined ? 1 : this.detail.status;
    },
    diff_num() {
      let group_num = parseInt(this.detail.group_num);
      let now_num = parseInt(this.detail.now_num);
      return !isNaN(group_num - now_num) ? group_num - now_num : 0;
    }
  },
  mounted() {
    if (this.$store.state.config.addons.groupshopping) {
      this.loadData();
    } else {
      this.$refs.load.fail({ errorText: "未开启团购应用", showFoot: false });
    }
  },
  methods: {
    loadData() {
      const $this = this;
      $this.$store
        .dispatch("getAssembleDetail", $this.$route.params.recordid)
        .then(detail => {
          $this.detail = detail;
          $this
            .onShare({
              title: $this.detail.goods.goods_name,
              desc: "快来帮我拼团吧！",
              imgUrl: $this.$BASESRC($this.detail.goods.pic_cover),
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
    },
    onJoin() {
      this.$router.push({
        name: "goods-detail",
        params: {
          goodsid: this.goods.goods_id
        },
        query: {
          record_id: this.detail.record_id
        }
      });
    },
    onInvite() {
      this.$Toast(
        this.$store.state.isWeixin
          ? "微信环境下点击右上角分享"
          : "手机浏览器点击底部工具栏分享"
      );
    },
    onCallback() {
      this.$Toast("拼团已结束！");
      this.detail.status = -1;
    }
  },
  components: {
    CountDown,
    Divider,
    GoodsCard,
    AvatarGroup,
    ResultState
  }
});
</script>
<style scoped>
.goods-card {
  padding: 0;
  background: #ffffff;
}

.avatar-group {
  padding: 20px 10px;
}

.info {
  padding-bottom: 20px;
}

.foot {
  width: 100%;
  display: flex;
  align-items: center;
}

.time-end {
  display: flex;
  align-items: center;
  margin-right: 10px;
}

.time-end span {
  color: #ffffff;
  background: #ff454e;
  padding: 2px 4px;
  border-radius: 2px;
}

.time-end i {
  font-style: normal;
  color: #ff454e;
  padding: 0 4px;
}

.success-text {
  color: #4b0;
}

.divider {
  margin: 10px 0;
}

.tips {
  text-align: center;
  padding: 10px;
}

.tips .num {
  color: #ff454e;
}

.btn-group {
  padding: 0 15px;
}

.btn-group .btn {
  margin: 10px 0;
}

.see-order {
  text-align: center;
  padding: 10px 0;
}
</style>
