<template>
  <div class="goods-seckill bg-f8">
    <template v-if="$store.state.config.addons.seckill">
      <Navbar />
      <div class="head">
        <div class="box">
          <van-tabs v-model="tab_active" class="seckill-tab" @change="onTab">
            <van-tab v-for="(item,index) in tabs" :key="index">
              <div slot="title">
                <div class="fw-blod">{{item.tag_name}}</div>
                <div class="fs-12">{{item.tag_status | tag_status}}</div>
              </div>
            </van-tab>
          </van-tabs>
        </div>
      </div>

      <List
        class="list"
        v-model="loading"
        :finished="finished"
        :error.sync="error"
        :is-empty="isListEmpty"
        :empty="{message: '没有相关秒杀商品'}"
        @load="loadList"
      >
        <van-cell v-for="(item,index) in list" :key="index" class="item">
          <GoodsCard :id="item.goods_id" :thumb="item.goods_img | BASESRC">
            <div slot="title" class="van-card__title title">{{item.goods_name}}</div>
            <div slot="tags" class="tags">
              <Progressbar
                :value="item.robbed_percent | filterPercent"
                :num="item.robbed_num"
                v-if="item.tag_status == 'started' || item.tag_status == 'going'"
              />
              <div
                class="remain-info"
                v-else="item.tag_status == 'unstart' || item.tag_status == 'tomorrow_start'"
              >
                <div>{{item.seckill_num}} 件</div>
                <span class="p-span">|</span>
                <div>{{item.rob_time}}</div>
              </div>
            </div>
            <div slot="bottom" class="foot">
              <div class="price">
                <span
                  class="seckill-price"
                  :class="item.tag_status == 'started' || item.tag_status == 'going'?'started':'nostarted'"
                >{{item.seckill_price | yuan}}</span>
                <span class="van-card__origin-price">{{item.price | yuan}}</span>
              </div>
              <van-button
                size="mini"
                type="danger"
                :to="'/goods/detail/'+item.goods_id"
                v-if="item.tag_status == 'started' || item.tag_status == 'going'"
              >马上抢</van-button>
              <van-button
                size="mini"
                type="primary"
                v-else="item.tag_status == 'unstart' || item.tag_status == 'tomorrow_start'"
                @click="onCollection(index)"
              >{{item.is_collection?'取消收藏':'收藏'}}</van-button>
            </div>
          </GoodsCard>
        </van-cell>
      </List>
    </template>
    <Empty v-else page-type="fail" message="未开启秒杀应用" :show-foot="false" />
  </div>
</template>

<script>
import sfc from "@/utils/create";
import GoodsCard from "@/components/GoodsCard";
import Progressbar from "./component/Progressbar";
import Empty from "@/components/Empty";
import { SET_GOODSCOLLECT, CANCEL_GOODSCOLLECT } from "@/api/goods";
import { GET_SECKILLTAG, GET_SECKILLLIST } from "@/api/seckill";
import { list } from "@/mixins";
export default sfc({
  name: "seckill-list",
  data() {
    return {
      tab_active: 0,
      tabs: [],

      params: {
        page_index: 1,
        page_size: 10,
        condition_time: "",
        condition_day: "",
        tag_status: ""
      }
    };
  },
  filters: {
    tag_status(value) {
      let status = "";
      if (value == "started") {
        status = "已开抢";
      } else if (value == "going") {
        status = "抢购中";
      } else if (value == "unstart") {
        status = "即将开抢";
      } else if (value == "tomorrow_start") {
        status = "明日开抢";
      }
      return status;
    },
    filterPercent(value) {
      return parseFloat(value);
    }
  },
  mixins: [list],
  mounted() {
    this.$store.state.config.addons.seckill && this.loadData();
  },
  methods: {
    onTab(index) {
      const $this = this;
      $this.params.condition_time = $this.tabs[index].condition_time;
      $this.params.condition_day = $this.tabs[index].condition_day;
      $this.params.tag_status = $this.tabs[index].tag_status;
      $this.loadList("init");
    },
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_SECKILLLIST($this.params)
        .then(({ data }) => {
          let list = data.sec_goods_list ? data.sec_goods_list : [];
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    },
    loadData() {
      const $this = this;
      GET_SECKILLTAG().then(({ data }) => {
        $this.tabs = data;
        $this.params.condition_time = $this.tabs[0].condition_time;
        $this.params.condition_day = $this.tabs[0].condition_day;
        $this.params.tag_status = $this.tabs[0].tag_status;
        $this.loadList();
      });
    },
    onCollection(index) {
      const $this = this;
      const item = $this.list[index];
      if (item.is_collection) {
        CANCEL_GOODSCOLLECT(item.goods_id).then(res => {
          $this.$Toast.success("取消成功");
          item.is_collection = false;
        });
      } else {
        SET_GOODSCOLLECT(item.goods_id, item.seckill_id).then(res => {
          $this.$Toast.success("收藏成功");
          item.is_collection = true;
        });
      }
    }
  },
  components: {
    Progressbar,
    GoodsCard,
    Empty
  }
});
</script>

<style scoped>
.head {
  height: 44px;
}

.head .box {
  height: 44px;
  width: 100%;
  position: fixed;
  z-index: 99;
}

.list {
  margin-top: 10px;
}

.seckill-tab >>> .van-tabs__wrap {
  background: #282831;
  overflow: initial;
}

.seckill-tab >>> .van-tab {
  background: #282831;
  color: #ffffff;
  line-height: 22px;
}

.seckill-tab >>> .van-tab--active {
  color: #ffffff;
  background: #ff454e;
}

.seckill-tab >>> .van-tabs__line {
  display: none;
}

.seckill-tab >>> .van-tab--active::after {
  content: "";
  position: absolute;
  border-top: 8px dashed;
  border-top: 4px solid \9;
  border-right: 8px solid transparent;
  border-left: 8px solid transparent;
  bottom: 8px;
  color: #ff454e;
  width: 0;
  height: 0;
  bottom: -7px;
  left: 50%;
  margin-left: -8px;
  z-index: 10;
}

.fw-blod {
  font-weight: 800;
}

.van-card {
  background: #ffffff;
}

.title {
  height: 40px;
}

.list .tags {
  display: flex;
  align-items: center;
}

.foot {
  width: 100%;
  display: flex;
  justify-content: space-between;
}

.foot .price {
  display: flex;
  align-items: center;
}

.remain-info {
  display: flex;
  color: #28b400;
}

.remain-info .p-span {
  padding: 0 8px;
}

.seckill-price {
  font-weight: 800;
  color: #ff454e;
  font-size: 14px;
  padding-right: 10px;
}

.seckill-price.nostarted {
  color: #28b400;
}
</style>
