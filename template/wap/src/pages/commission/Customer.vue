<template>
  <div class="commission-customer bg-f8">
    <Navbar :title="navbarTitle" />
    <List
      class="list"
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      @load="loadList"
    >
      <van-cell-group>
        <van-cell class="item" v-for="(item,index) in list" :key="index">
          <div class="box">
            <div class="img">
              <img v-lazy="item.user_headimg" :key="item.user_headimg" pic-type="square" />
            </div>
            <div class="info">
              <div>
                <span class="name">{{item.nick_name?item.nick_name:item.user_name}}</span>
                <span class="level-name">{{item.member_level_name}}</span>
              </div>
              <div>
                成交订单：
                <span>{{item.order_count}}</span>
              </div>
            </div>
          </div>
        </van-cell>
      </van-cell-group>
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import { GET_CUSTOMERLIST } from "@/api/commission";
import { list } from "@/mixins";
export default sfc({
  name: "commission-customer",
  data() {
    return {};
  },
  mixins: [list],
  computed: {
    navbarTitle() {
      const { my_customer } = this.$store.state.member.commissionSetText;
      let title = my_customer;
      document.title = title;
      return title;
    }
  },
  mounted() {
    this.loadList();
  },
  activated() {
    if (this.navbarTitle) {
      document.title = this.navbarTitle;
    }
  },
  methods: {
    loadList(init) {
      const $this = this;
      GET_CUSTOMERLIST($this.params)
        .then(({ data }) => {
          let list = data.data ? data.data : [];
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    }
  }
});
</script>

<style scoped>
.item .box {
  display: flex;
  align-items: center;
}

.item .img {
  width: 60px;
  height: 60px;
  margin-right: 10px;
}

.item .img img {
  display: block;
  width: 100%;
  height: 100%;
}

.item .info {
  flex: 1;
}

.item .info .level-name {
  padding-left: 10px;
  color: #606266;
}
</style>
