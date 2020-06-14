<template>
  <div class="commission-order bg-f8">
    <Navbar :title="navbarTitle" />
    <HeadTab v-model="tab_active" :tabs="tabs" @tab-change="onTab" />
    <List
      class="list"
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      :empty="{pageType:'order',message: '没有相关订单'}"
      @load="loadList"
    >
      <van-collapse
        v-model="activeNames"
        accordion
        class="item card-group-box"
        v-for="(item,index) in list"
        :key="index"
      >
        <van-collapse-item :name="index">
          <div slot="title">
            <div class="title">{{item.order_no}}</div>
            <div class="time">{{item.create_time | formatDate}}</div>
          </div>
          <div slot="value">
            <div>+{{item.commission ? item.commission : 0}}</div>
            <div class="status">{{item.status_name}}</div>
          </div>
          <van-cell class="user">
            <div class="info">
              <div class="img">
                <img :src="item.buyer_headimg | BASESRC" :onerror="$ERRORPIC.noAvatar" />
              </div>
              <div class="text">
                <span>{{buyerInfoText(item)}}</span>
              </div>
            </div>
          </van-cell>
          <van-cell class="goods">
            <GoodsCard
              :id="goods.goods_id"
              :title="goods.goods_name"
              :thumb="goods.picture.pic_cover_mid | BASESRC"
              v-for="(goods,i) in item.order_item_list"
              :key="i"
            >
              <div slot="tags">
                <div class="text-right">x {{goods.num}}</div>
              </div>
              <div slot="bottomRight">
                <div>{{$store.state.member.commissionSetText.commission}}：{{goods.commission}}</div>
              </div>
            </GoodsCard>
          </van-cell>
        </van-collapse-item>
      </van-collapse>
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import { Collapse, CollapseItem } from "vant";
import HeadTab from "@/components/HeadTab";
import GoodsCard from "@/components/GoodsCard";
import { GET_ORDERLIST } from "@/api/commission";
import { list } from "@/mixins";
export default sfc({
  name: "commission-order",
  data() {
    return {
      tab_active: 0,
      tabs: [
        {
          name: "所有订单",
          status: null
        },
        {
          name: "已付款",
          status: 1
        },
        {
          name: "已发货",
          status: 2
        },
        {
          name: "已完成",
          status: 4
        }
      ],

      activeNames: "",

      params: {
        page_index: 1,
        order_status: null
      }
    };
  },
  computed: {
    navbarTitle() {
      const { distribution_order } = this.$store.state.member.commissionSetText;
      let title = distribution_order;
      document.title = title;
      return title;
    }
  },
  mixins: [list],
  mounted() {
    this.loadList();
  },
  activated() {
    if (this.navbarTitle) {
      document.title = this.navbarTitle;
    }
  },
  methods: {
    onTab(index) {
      const $this = this;
      const status = $this.tabs[index].status;
      $this.params.order_status = status;
      $this.loadList("init");
    },
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_ORDERLIST($this.params)
        .then(({ data }) => {
          let list = data.data ? data.data : [];
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    },
    buyerInfoText({
      buyer_nick_name,
      buyer_user_tel,
      buyer_user_name,
      buyer_id
    }) {
      if (buyer_nick_name) return buyer_nick_name;
      if (buyer_user_tel) return buyer_user_tel;
      if (buyer_user_name) return buyer_user_name;
      if (buyer_id) return buyer_id;
    }
  },
  components: {
    HeadTab,
    GoodsCard,
    [Collapse.name]: Collapse,
    [CollapseItem.name]: CollapseItem
  }
});
</script>

<style scoped>
.item .title {
  white-space: nowrap;
}

.item .time {
  font-size: 12px;
  color: #606266;
}

.item .status {
  color: #ff454e;
}

.item >>> .van-collapse-item__content {
  padding: 0;
}

.van-card {
  margin-top: 0;
}

.user .info {
  display: flex;
  align-items: center;
}
.user .info .img {
  width: 50px;
  height: 50px;
  overflow: hidden;
}
.user .info .img img {
  width: 100%;
  height: 100%;
  display: block;
}
.user .info .text {
  flex: 1;
  padding-left: 8px;
}
</style>
